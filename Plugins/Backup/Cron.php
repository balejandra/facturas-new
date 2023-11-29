<?php
namespace FacturaScripts\Plugins\Backup;

use FacturaScripts\Core\Base\CronClass;
use FacturaScripts\Plugins\Backup\Lib\BackupDrive;

//use FacturaScripts\Core\Template\CronClass;


class Cron extends CronClass
{
    public function run()
    {

        if ($this->isTimeForJob("backup-and-upload-job", "5 minutes")) {
            $backupController = new BackupDrive();
            $backupController->backupAndUploadToDrive();
            $this->jobDone("backup-and-upload-job");
        }

    }
}
