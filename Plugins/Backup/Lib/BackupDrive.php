<?php


namespace FacturaScripts\Plugins\Backup\Lib;

use Coderatio\SimpleBackup\SimpleBackup;
use Exception;
use Google\Client;
use Google\Service\Drive;
use FacturaScripts\Core\Base\FileManager;
use Google\Service\Drive\DriveFile;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;
use FacturaScripts\Plugins\Backup\Model\CarpetaGoogleDrive;


class BackupDrive
{

    /**
     * Return the max file size that can be uploaded.
     *
     * @return float
     */
    public function getMaxFileUpload()
    {
        return UploadedFile::getMaxFilesize() / 1024 / 1024;
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
            return;
        }

        $simpleBackupDB = SimpleBackup::setDatabase([FS_DB_NAME, FS_DB_USER, FS_DB_PASS, FS_DB_HOST])->storeAfterExportTo($localBackupPath, FS_DB_NAME . '_' . date('Y-m-d_H-i-s'));
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
                unlink($localFilePath);
            }
        } catch (Exception $e) {
            echo ($e->getMessage());
        }
    }

    public function backupAndUploadToDrive(): void
    {
       // $this->backupAndUploadFilesToDrive();
        $this->backupAndUploadDBToDrive();
    }
}
