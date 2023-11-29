<?php

namespace FacturaScripts\Plugins\Backup\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Session;

class CarpetaGoogleDrive extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $creationdate;

    /** @var int */
    public $id;

    /** @var string */
    public $idgoogledrive;

    /** @var string */
    public $lastnick;

    /** @var string */
    public $lastupdate;

    /** @var string */
    public $name;

    /** @var string */
    public $nick;

    public function clear()
    {
        parent::clear();
    }

    public static function primaryColumn(): string
    {
        return "id";
    }

    public static function tableName(): string
    {
        return "carpetas_google_drive";
    }

    public function test(): bool
    {
        if ($this->exists()) {
            $this->lastnick = Session::user()->nick;
            $this->lastupdate = date(self::DATETIME_STYLE);
        } else {
            $this->creationdate = date(self::DATETIME_STYLE);
            $this->lastnick = null;
            $this->lastupdate = null;
            $this->nick = Session::user()->nick;
        }

        $this->idgoogledrive = $this->toolBox()->utils()->noHtml($this->idgoogledrive);
        $this->name = $this->toolBox()->utils()->noHtml($this->name);
        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'Backup?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
