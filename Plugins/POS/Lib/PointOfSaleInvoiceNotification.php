<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Lib\Email\MailNotifier;
use FacturaScripts\Dinamic\Lib\Export\PDFExport;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\FacturaCliente;

class PointOfSaleInvoiceNotification
{
    const FILE_NAME =  FS_FOLDER . '/MyFiles/';

    function sendInvoiceNotification(SalesDocument $document)
    {
        $sentEmail=false;
        $cliente = new Cliente();
        $cliente->loadFromCode($document->codcliente);
        $factura = new FacturaCliente();
        if ($factura->loadFromCode($document->idfactura)) {
            $factura->editable = false;
            $factura->idestado = (int)11;
            $factura->saveUpdate();
            $pdf = new PDFExport();
            $pdf->addBusinessDocPage($factura);
            $file_pdf = self::FILE_NAME . $document->codigo . 'email.pdf';
            if (file_put_contents($file_pdf, $pdf->getDoc())) {

                $email = MailNotifier::send('sendmail-FacturaCliente', $cliente->email, $cliente->nombre, [
                    'code' => $factura->codigo,
                    'date' => $factura->fecha
                ], [$file_pdf]);
                if ($email) {
                    // Eliminar el archivo después de enviar el correo si es necesario
                    unlink($file_pdf);
                    $sentEmail = true;
                } else {
                    $sentEmail = false;
                }
            } else {
                $sentEmail = false;
            }
        } else {
            $sentEmail = false;
        }

        return $sentEmail;
    }
}
