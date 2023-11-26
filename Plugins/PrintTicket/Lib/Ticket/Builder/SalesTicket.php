<?php
/*
 * This file is part of PrintTicket plugin for FacturaScripts
 * Copyright (c) 2021.  Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
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

namespace FacturaScripts\Plugins\PrintTicket\Lib\Ticket\Builder;

use FacturaScripts\Core\Base\NumberTools;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FormatoTicket;
use FacturaScripts\Dinamic\Model\OperacionPausada;

class SalesTicket extends AbstractTicketBuilder
{
    /**
     * @var SalesDocument
     */
    protected $document;


    public function __construct(SalesDocument $document, ?FormatoTicket $formato)
    {
        parent::__construct($formato);

        $this->document = $document;
        $this->ticketType = $document->modelClassName();
    }

    /**
     * Builds the ticket head
     */
    protected function buildHeader(): void
    {
        $company = $this->document->getCompany();

        $this->printer->lineBreak();
        $this->setTitleFontStyle();

        //$this->printLogo();
        $this->printer->textCentered($company->nombrecorto);
        $this->printer->textCentered($company->direccion);
        $this->printer->textCentered($company->telefono1);
        $this->printer->textCentered($company->cifnif);

        $this->resetFontStyle();
        $this->printer->lineSeparator('=');

        foreach ($this->getCustomLines('header') as $line) {
            $this->printer->textCentered($line);
        }
    }

    /**
     * Builds the ticket body
     */
    protected function buildBody(): void
    {
        $this->printer->textCentered($this->document->codigo);
        $fechacompleta = $this->document->fecha . ' ' . $this->document->hora;
        $this->printer->textCentered($fechacompleta);

        $this->printer->text('CLIENTE: ' . $this->document->nombrecliente);
        $this->printer->lineSeparator();

        $this->setBodyFontSize();
        if (self::PRICE_NO_PRICE === $this->formato->formato_precio) {
            $this->buildLinesWithoutPrices();
            return;
        }

        $this->buildLinesWithPrices();

        if (self::PRICE_AFTER_TAX === $this->formato->formato_precio) {
            $this->printer->textKeyValue('IVA Incluido:', NumberTools::format($this->document->totaliva));
        } else {
            $this->printer->textKeyValue('Base:', NumberTools::format($this->document->neto));
            $this->printer->textKeyValue('IVA:', NumberTools::format($this->document->totaliva));
        }

        $this->printer->textKeyValue('Total:', NumberTools::format($this->document->total));
    }

    protected function buildLinesWithPrices()
    {
        $this->printer->setFontBold();
        $this->printer->textCentered('ARTICULOS');
        $text = $this->printer->textToColumn(3, 'CANTIDAD', '-');
        $text .= $this->printer->textToColumn(3, 'P.UNITARIO', '-');
        $text .= $this->printer->textToColumn(3, 'IMPORTE');
        $this->printer->text($text);
        $this->printer->setFontBold(false);

        $this->printer->lineSeparator();

        $counter = 0;
        foreach ($this->document->getLines() as $line) {
            if (self::PRICE_AFTER_TAX === $this->formato->formato_precio) {
                $printablePrice = $this->getPriceWithTax($line);
            } else {
                $printablePrice = $line->pvpunitario;
            }

            $printableTotal = $printablePrice * $line->cantidad;

            $itemLine = $this->printer->textToColumn(3, $line->cantidad, '-');
            $itemLine .= $this->printer->textToColumn(3, NumberTools::format($printablePrice), '-');
            $itemLine .= $this->printer->textToColumn(3, NumberTools::format($printableTotal));

            $this->printer->text("$line->referencia - $line->descripcion");
            $this->printer->text($itemLine);

            $counter += $line->cantidad;
            $this->printer->lineBreak();
        }
        $this->printer->lineBreak();
        $this->printer->textCentered('Total de articulos: ' . $counter);

        $this->printer->lineSeparator();
    }

    protected function buildLinesWithoutPrices()
    {
        $this->printer->setFontBold();
        $text = $this->printer->textToColumn(6, 'CANT.', '-') . 'DESCRIPCION';
        $this->printer->text($text);
        $this->printer->setFontBold(false);

        $this->printer->lineSeparator();

        $counter = 0;
        foreach ($this->document->getLines() as $line) {
            $cantidad = $this->printer->textToColumn(6, $line->cantidad, '-');
            $this->printer->text("$cantidad $line->referencia - $line->descripcion");

            $counter += $line->cantidad;
            $this->printer->lineBreak();
        }

        $this->printer->lineBreak();
        $this->printer->textCentered('Total de articulos: ' . $counter);

        $this->printer->lineSeparator();
    }

    /**
     * Builds the ticket foot
     */
    protected function buildFooter(): void
    {
        $this->printer->lineBreak(2);

        foreach ($this->getCustomLines('footer') as $line) {
            $this->printer->text($line);
        }

        $this->printer->lineBreak(2);
        $this->printer->barcode($this->document->codigo);
    }

    /**
     * @return array
     */
    public function getDataAsArray(): array
    {
        $company = $this->document->getCompany();
        $codigo='';
        $formato_precio=0;

        if ($this->document instanceof OperacionPausada) {
            $codigo = $this->document->idpausada;
            $formato_precio = 3;
        } elseif ($this->document instanceof FacturaCliente) {
            $codigo = $this->document->codigo;
            $formato_precio = $this->formato->formato_precio;
        } else {
            // No es ni una operación pausada ni una factura
            var_dump('otro tipo');
        }

        $data = [
            'tipo_ticket' => 'sale_ticket',
            'nombrecorto' => $company->nombrecorto,
            'direccion' => $company->direccion,
            'telefono1' => $company->telefono1,
            'cifnif' => $company->cifnif,
            'codigo' => $codigo,
            'fechacompleta' => $this->document->fecha . ' ' . $this->document->hora,
            'cliente' => $this->document->nombrecliente,
            'neto' => $this->document->neto,
            'totaliva' => $this->document->totaliva,
            'total' => $this->document->total,
            'formato_precio' => $formato_precio,
            'anchoFormato' => $this->formato->ancho,
            'lines' => [],
        ];
        $headerLines = [];
        foreach ($this->getCustomLines('header') as $line) {
            $headerLines[] = $line;
        }

        $data['headerCustomLines'] = $headerLines;

        $footerLines = [];
        foreach ($this->getCustomLines('footer') as $line) {
            $footerLines[] = $line;
        }
        $data['footerCustomLines'] = $footerLines;
        $lineData = [];
        $counter = 0;
        foreach ($this->document->getLines() as $line) {
            $printablePrice = 0;
            if (self::PRICE_AFTER_TAX === $this->formato->formato_precio) {
                $printablePrice = $this->getPriceWithTax($line);
            } else {
                $printablePrice = $line->pvpunitario;
            }

            $unitarioTotal = $printablePrice * $line->cantidad;

            $lineData = [
                'referencia' => $line->referencia,
                'descripcion' => $line->descripcion,
                'cantidad' => $line->cantidad,
                'pvpunitario' => $printablePrice,
                'totalunitario' => $unitarioTotal

            ];
            $counter += $line->cantidad;
            $data['lines'][] = $lineData;
        }
        $data['totalArticulos'] = $counter;

        $this->printer->getBuffer();
        return $data;
    }

    /**
     * @return string
     */
    public function getResult(): string
    {
        $this->buildHeader();
        $this->buildBody();
        $this->buildFooter();

        $this->printer->lineBreak(3);

        return $this->printer->getBuffer();
    }

    protected function getPriceWithTax($line): string
    {
        return floatval($line->pvpunitario) * (100 + floatval($line->iva)) / 100;
    }
}
