<?php
namespace FacturaScripts\Plugins\Backup\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

class EditCarpetaGoogleDrive extends EditController
{
    public function getModelClassName(): string
    {
        return "CarpetaGoogleDrive";
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data["title"] = "CarpetaGoogleDrive";
        $data["icon"] = "fas fa-search";
        return $data;
    }
}
