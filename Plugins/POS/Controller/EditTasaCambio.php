<?php
namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

class EditTasaCambio extends EditController
{
    public function getModelClassName(): string
    {
        return "TasaCambio";
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data["title"] = "TasasCambio";
        $data["icon"] = "fas fa-search";
        return $data;
    }
}
