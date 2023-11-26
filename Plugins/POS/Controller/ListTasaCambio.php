<?php

namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\Lib\ExtendedController\ListController;

class ListTasaCambio extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data["title"] = "Tasas de Cambio";
        $data["menu"] = "point-of-sale";
        $data["icon"] = "fas fa-exchange-alt";
        return $data;
    }

    protected function createViews()
    {
        $this->createViewsTasasCambio();
    }

    protected function createViewsTasasCambio(string $viewName = "ListTasaCambio")
    {
        $this->addView($viewName, "TasaCambio", "Tasas de Cambio");

        // Esto es un ejemplo ... debe de cambiarlo según los nombres de campos del modelo
        // $this->addOrderBy($viewName, ["id"], "id", 2);
        // $this->addOrderBy($viewName, ["name"], "name");

        // Esto es un ejemplo ... debe de cambiarlo según los nombres de campos del modelo
        // $this->addSearchFields($viewName, ["id", "name"]);
    }
}
