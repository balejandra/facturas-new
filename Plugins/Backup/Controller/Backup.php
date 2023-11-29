<?php

/**
 * This file is part of Backup plugin for FacturaScripts
 * Copyright (C) 2021-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\Backup\Controller;

use Coderatio\SimpleBackup\SimpleBackup;
use Exception;
use Google\Client;
use Google\Service\Drive;
use FacturaScripts\Core\Base\FileManager;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Dinamic\Model\User;
use Google\Service\Drive\DriveFile;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use ZipArchive;
use FacturaScripts\Core\Lib\ExtendedController\PanelController;
use FacturaScripts\Plugins\Backup\Model\CarpetaGoogleDrive;
use FacturaScripts\Core\Tools;

/**
 * Backup and restore database and user files of application
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Backup  extends PanelController
{

    protected function createViewListCarpet(string $viewName = 'ListCarpetaGoogleDrive')
    {
        $this->addListView($viewName, 'CarpetaGoogleDrive', 'Carpeta Drive', 'fas fa-folder');
        $this->views[$viewName]->addOrderBy(['id'], 'id');
        $this->views[$viewName]->addSearchFields(['idgoogledrive']);
    }

    protected function createViewHtml(string $viewName = 'Backup')
    {
        $this->addHtmlView($viewName, 'Tab/Backup', 'CarpetaGoogleDrive', 'backup', 'fas fa-download');
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->setTemplate('EditSettings');
        $this->createViewListCarpet();
        $this->createViewHtml();

        //$this->setTabsPosition('bottom');
    }

    protected function loadData($viewName, $view)
    {
        $this->hasData = true;

        switch ($viewName) {
            case 'Backup':
                $this->defaultChecks();
                break;

            case 'ListCarpetaGoogleDrive':
                $view->loadData();
                break;
        }
    }


    /**
     * Return the max file size that can be uploaded.
     *
     * @return float
     */
    public function getMaxFileUpload()
    {
        return UploadedFile::getMaxFilesize() / 1024 / 1024;
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'backup';
        $data['icon'] = 'fas fa-download';
        return $data;
    }




    /**
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $action = $this->request->get('action', '');
        switch ($action) {
            case 'download-db':
                $this->downloadDbAction();
                break;

            case 'download-files':
                $this->downloadFilesAction();
                break;

            case 'restore-backup':
                $this->restoreBackupAction();
                break;

            case 'backup-upload-drive':
                $this->backupAndUploadToDrive();
                break;

            default:
                $this->defaultChecks();
                break;
        }
    }

    private function defaultChecks(): void
    {
        // obtenemos el límite de memoria
        $memoryLimit = ini_get('memory_limit');
        switch (substr($memoryLimit, -1)) {
            case 'G':
                $memoryMb = substr($memoryLimit, 0, -1) * 1024;
                break;

            case 'M':
                $memoryMb = substr($memoryLimit, 0, -1);
                break;

            case 'K':
                $memoryMb = round(substr($memoryLimit, 0, -1) / 1024, 2);
                break;

            default:
                $memoryMb = (int)$memoryLimit;
                break;
        }

        // calculamos el tamaño de la carpeta FS_FOLDER
        $folderSize = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(FS_FOLDER),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $folderSize += $file->getSize();
        }
        $folderMb = round($folderSize / 1024 / 1024, 2);

        // si la carpeta FS_FOLDER ocupa más que el límite de memoria, mostramos un aviso
        if ($folderMb >= $memoryMb) {
            $this->toolBox()->i18nLog()->warning('backup-memory-warning', [
                '%size%' => $folderMb,
                '%memory%' => $memoryMb
            ]);
        }
    }

    private function downloadDbAction(): void
    {
        if (FS_DB_TYPE != 'mysql') {
            self::toolBox()::log()->error('mysql-support-only');
            return;
        }

        if (false === extension_loaded('pdo_mysql')) {
            self::toolBox()::log()->error('pdo-mysql-support-only');
            return;
        }

        $this->setTemplate(false);
        SimpleBackup::setDatabase([FS_DB_NAME, FS_DB_USER, FS_DB_PASS, FS_DB_HOST])
            ->downloadAfterExport(FS_DB_NAME . '_' . date('Y-m-d_H-i-s'));
    }

    private function downloadFilesAction(): void
    {
        $filePath = FS_FOLDER . '/' . FS_DB_NAME . '.zip';
        if (false === $this->zipFolder($filePath)) {
            $this->toolBox()->i18nLog()->error('record-save-error');
            return;
        }

        $this->setTemplate(false);
        $this->response = new BinaryFileResponse($filePath);
        $this->response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            FS_DB_NAME . '_' . date('Y-m-d_H-i-s') . '.zip'
        );
    }

    private function restoreBackupAction(): void
    {
        if (false === $this->validateFormToken()) {
            return;
        }

        $dbFile = $this->request->files->get('dbfile');
        if (empty($dbFile)) {
            return;
        }

        $this->dataBase->close();
        $backup = SimpleBackup::setDatabase([FS_DB_NAME, FS_DB_USER, FS_DB_PASS, FS_DB_HOST])->importFrom($dbFile->getPathname());
        if (false === $backup->getResponse()->status) {
            $this->toolBox()->i18nLog()->error('record-save-error');
            $this->dataBase->connect();
            return;
        }

        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        $this->dataBase->connect();
    }

    private function zipFolder(string $fileName): bool
    {
        $zip = new ZipArchive();
        if (false === $zip->open($fileName, ZIPARCHIVE::CREATE | ZipArchive::OVERWRITE)) {
            return false;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(FS_FOLDER),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if ($file->isDir() || substr($name, -4) === '.zip') {
                continue;
            }

            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen(FS_FOLDER) + 1);

            $zip->addFile($filePath, $relativePath);
        }

        return $zip->close();
    }

    private function backupAndUploadFilesToDrive(): void
    {
        $localBackupPath = FS_FOLDER . '/MyFiles/Backup/';
        if (false === FileManager::createFolder($localBackupPath, true)) {
            $this->toolBox()->i18nLog()->critical('cant-create-folder', ['%folderName%' => $localBackupPath]);
            return;
        }

        /******FILES**********/

        // Nombre del archivo de copia de seguridad
        $backupFileName = 'backup_' . date('Y-m-d_H-i-s') . '.zip';
        $localBackupFile = $localBackupPath . $backupFileName;

        // Realizar la copia de seguridad local
        $this->zipFolder($localBackupFile);

        // Subir la copia de seguridad a Google Drive
        $this->uploadToDrive($localBackupFile, $backupFileName, 'Backup Archivos Facturas');
    }

    private function backupAndUploadDBToDrive()
    {
        $localBackupPath = FS_FOLDER . '/MyFiles/Backup/';
        if (false === FileManager::createFolder($localBackupPath, true)) {
            $this->toolBox()->i18nLog()->critical('cant-create-folder', ['%folderName%' => $localBackupPath]);
            return;
        }
        /******DB**********/
        if (FS_DB_TYPE != 'mysql') {
            self::toolBox()::log()->error('mysql-support-only');
            return;
        }

        if (false === extension_loaded('pdo_mysql')) {
            self::toolBox()::log()->error('pdo-mysql-support-only');
            return;
        }

        $this->setTemplate(false);
        $simpleBackupDB = SimpleBackup::setDatabase([FS_DB_NAME, FS_DB_USER, FS_DB_PASS, FS_DB_HOST])
            ->storeAfterExportTo($localBackupPath, FS_DB_NAME . '_' . date('Y-m-d_H-i-s'));
        $localBackupDB = $localBackupPath . $simpleBackupDB->getExportedName();
        $this->uploadToDrive($localBackupDB, $simpleBackupDB->getExportedName(), 'Backup Base de Datos Facturas');
    }

    private function uploadToDrive(string $localFilePath, string $fileName, string $description): void
    {
        $idfolder = '';
        $folderDrive = new CarpetaGoogleDrive();
        // Obtener todos los registros de 'CarpetaGoogleDrive' en la base de datos
        $folderRecords = $folderDrive->all([], [], 0, 1);
        if (!empty($folderRecords)) {
            $firstFolder = $folderRecords[0];
            $idfolder = $firstFolder->idgoogledrive;
        } else {
            $this->toolBox()->i18nLog()->error(
                'No existen registros de Carpeta Google Drive en la base de datos'
            );
            return;
        }

        $claveJSON = $idfolder;
        $pathJSON = FS_FOLDER . '/MyFiles/' . 'credentials.json';

        $client = new Client();
        $client->setAuthConfig($pathJSON);
        //  $client->setAccessToken($this->checkAccessToken());
        $client->useApplicationDefaultCredentials();
        $client->setScopes(['https://www.googleapis.com/auth/drive.file']);
        try {
            //instanciamos el servicio
            $service = new Drive($client);

            //instacia de archivo
            $file = new DriveFile();
            $file->setName($fileName);

            //id de la carpeta donde hemos dado el permiso a la cuenta de servicio
            $file->setParents(array($claveJSON));
            $file->setDescription($description);
            $result = $service->files->create(
                $file,
                array(
                    'data' => file_get_contents($localFilePath),
                    'mimeType' => 'application/octet-stream',
                    'uploadType' => 'media',
                )
            );
            if ($result) {
                $this->toolBox()->i18nLog()->info('Backup Almacenado Exitosamente');
                unlink($localFilePath);
            }
        } catch (Exception $e) {
            Tools::log()->error('Ha ocurrido un error: ' . $e->getMessage());
        }
    }


    private function backupAndUploadToDrive(): void
    {
        $this->backupAndUploadFilesToDrive();
        $this->backupAndUploadDBToDrive();
    }
}
