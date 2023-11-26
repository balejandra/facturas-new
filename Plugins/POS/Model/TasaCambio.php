<?php
namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Session;

class TasaCambio extends ModelClass
{
    use ModelTrait;


    public $coddivisadestino;

    public $coddivisaorigen;

    public $creationdate;

    public $id;

    public $lastupdate;

    public $nombre;

    public $nickusuario;

    public $tasacambio;

    public function clear()
    {
        parent::clear();
        $this->tasacambio = 0.0;
    }

    public static function primaryColumn(): string
    {
        return "id";
    }

    public function primaryDescriptionColumn(): string
    {
        return 'nombre';
    }


    public static function tableName(): string
    {
        return "tasascambiopos";
    }

    public function test(): bool
    {
        if ($this->exists()) {
            $this->nickusuario = Session::user()->nick;
            $this->lastupdate = date(self::DATETIME_STYLE);
        } else {
            $this->creationdate = date(self::DATETIME_STYLE);
            $this->lastupdate = null;
            $this->nickusuario = Session::user()->nick;
        }

        $this->coddivisadestino = $this->toolBox()->utils()->noHtml($this->coddivisadestino);
        $this->coddivisaorigen = $this->toolBox()->utils()->noHtml($this->coddivisaorigen);
        $this->nombre = $this->toolBox()->utils()->noHtml($this->nombre);
        return parent::test();
    }
}
