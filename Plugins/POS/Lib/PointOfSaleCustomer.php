<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Dinamic\Model\Cliente;

class PointOfSaleCustomer
{
    /**
     * @var Cliente
     */
    protected $customer;

    public function __construct()
    {
        $this->customer = new Cliente();
    }

    /**
     * @return Cliente
     */
    public function getCustomer(): Cliente
    {
        return $this->customer;
    }

    /**
     * @return array
     */
    public function getLastCustomerCode(): array
    {
        return ['code' => $this->customer->codcliente, 'description' => $this->customer->nombre];
    }

    /**
     * @param string $taxID
     * @param string $name
     * @param string $email
     * @param string $phone
     * @return bool
     */
    public function saveNew(string $taxID, string $name, string $email, string $phone): bool
    {
        $this->customer->cifnif = $taxID;
        $this->customer->nombre = $name;
        $this->customer->razonsocial = $name;
        $this->customer->email = $email;
        $this->customer->telefono1 = $phone;

        return $this->customer->save();
    }

    /**
     * @param string $text
     * @return array
     */
    public function search(string $text): array
    {
        return $this->getCustomer()->codeModelSearch($text);
    }
}
