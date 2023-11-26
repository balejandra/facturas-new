const query = window.location.search;
const params = new URLSearchParams(query);
const code = params.get('code');

export function showPrinterDialog(documentName) {
    if (code === undefined || code == null) {
        return;
    }

    const data = new FormData();
    data.append('action', 'get-formats')
    data.append('tipo-documento', documentName);

    fetch('PrintTicket', {method: 'POST', body: data})
        .then(response => response.json())
        .then(data => {
            for (const item of data) {
                item.label = item.nombre;
                item.className = 'btn-primary btn-block';
                item.callback = function () {
                    printRequest({
                        documentCode: code,
                        documentFormat: item.id,
                        documentName: documentName
                    });
                }
            }

            bootbox.dialog({
                message: '<h4>¿Qué típo de impresión deseas?</h4>',
                size: 'medium',
                buttons: data
            });
        })
        .catch(error => showPrintMessage('Error al obtener los formatos de impresion: ' + error));
}

function printRequest({documentCode, documentFormat, documentName}) {
    const data = new FormData();

    data.append('action', 'print-document');
    data.append('codigo', documentCode);
    data.append('formato', documentFormat);
    data.append('tipo', documentName);

    fetch('PrintTicket', {method: 'POST', body: data})
        .then(response => response.json())
        .then(data => {
            // return printerServiceRequest(data);
            const modalContent = buildModalContent(data, documentName);
			openModal(modalContent);
        })
        .catch(error => showPrintMessage('No se pudo conectar al servicio de impresion: '  + error));
}

function showPrintMessage(message) {
    bootbox.alert({title: 'Error', message: message});
}

/*async function printerServiceRequest({code}) {
    let params = new URLSearchParams({"documento": code});

    await fetch('http://localhost:8089?' + params, {
        mode: 'no-cors',
        method: 'GET'
    });
}*/
function buildModalContent(data, documentName) {
	return buildModalHtml(data, documentName);
}

function openModal(content) {
	bootbox.dialog({
		message: content,
		size: "medium",
    });
    const printWindow = window.open("", "_blank");

		printWindow.document.write(
			"<html><head></head><body>"
		);
		printWindow.document.write(content);
		printWindow.document.write("</body></html>");

		printWindow.document.close(); // Cerrar el documento antes de imprimir
        printWindow.print();
        printWindow.window.close();
        bootbox.hideAll();
}

function buildModalHtml(data, documentName) {
    const template = `
    <style>
        .ticket, .printer-ticket {
            font-family: Tahoma, Geneva, sans-serif;
            font-size: 12px;
        }

        .linea-divisora {
            border-bottom: 1px solid black;
            padding-bottom: 10px;
        }

        .centrado {
            text-align: center;
            align-content: center;
        }

        .text-center {
            text-align: center;
        }

        .printer-ticket {
            display: table !important;
            width: 100%;
            font-weight: light;
            line-height: 1.3em;
        }

        .printer-ticket, .printer-ticket * {
            background-color: #fff;
        }

        .printer-ticket th:nth-child(1), .printer-ticket td:nth-child(1) {
            width: 50px;
        }

        .printer-ticket th:nth-child(3) {
            width: 90px;
        }

        .printer-ticket td:nth-child(3), .printer-ticket td:nth-child(4) {
            width: 90px;
            text-align: right;
        }

        .printer-ticket th {
            font-weight: inherit;
            padding: 10px 0;
            text-align: center;
            border-bottom: 1px dashed #BCBCBC;
        }

        .printer-ticket tbody tr:last-child td {
            padding-bottom: 10px;
        }

        .printer-ticket tfoot .sup td {
            padding: 10px 0;
            border-top: 1px dashed #BCBCBC;
        }

        .printer-ticket tfoot .sup.p--0 td {
            padding-bottom: 0;
        }

        .printer-ticket .top td {
            padding-top: 10px;
        }

        .printer-ticket .last td {
            padding-bottom: 10px;
        }
    </style>
    <div id="ticketPrintingArea">
        <div class="ticket" style="width:${data.text.anchoFormato}%;margin-top:30px;margin-bottom=30px;">
            <div>
                <p class="centrado">${data.text.nombrecorto}<br>${data.text.direccion}<br>${data.text.cifnif}</p>
                <p class="centrado linea-divisora"></p>
                <p class="centrado">Comprobante:${data.text.codigo}</p>
                <p class="centrado"><br>${data.text.fechacompleta}<br>Cliente:${data.text.cliente}</p>
                <p class="centrado linea-divisora"></p>
                ${
                    data.text.headerCustomLines.length > 0 ?
                    data.text.headerCustomLines.map(header => `<p class="centrado">${header}</p>`).join('') :
                    ''
                }
                <br>
                <table class="printer-ticket">
                    <thead>
                        <tr>
                            ${
                                data.text.formato_precio === 3 ?
                                '<th colspan="2"><b>CANT</b></th><th colspan="2"><b>ARTICULOS</b></th>' :
                                '<th><b>CANT</b></th><th><b>ARTICULOS</b></th><th><b>PRECIO</b></th><th><b>IMPORTE</b></th>'
                            }
                        </tr>
                    </thead>
                    <tbody>
                        ${data.text.lines.map(line => `
                                <tr>
                                    ${
                                        data.text.formato_precio === 3 ?
                                        `<td colspan="2">${line.cantidad}</td><td colspan="2">${line.referencia}</td>` :
                                        `<td>${line.cantidad}</td><td>${line.referencia}</td><td>${line.pvpunitario}$</td><td>${line.totalunitario}$</td>`
                                    }
                                </tr>`).join('')
                        }
                        <tr>
                            <td colspan="4"><br>Total de articulos:${data.text.totalArticulos}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        ${
                            data.text.formato_precio !== 3 ?
                            '<tr class="sup p--0"><td colspan="4"><b>Totales</b></td></tr>' +
                            `<tr><td colspan="3">Base:</td><td align="right">${data.text.neto}$</td></tr>` +
                            `<tr><td colspan="3">IVA</td><td align="right">${data.text.totaliva}$</td></tr>` +
                            `<tr><td colspan="3">Total:</td><td align="right">${data.text.total}$</td></tr>` :
                            ''
                        }
                        <tr class="sup">
                            <td colspan="4" align="center"><b>Comprobante:${data.text.codigo}</b></td>
                        </tr>
                    </tfoot>
                </table>
                <br>
                ${
                    data.text.footerCustomLines.length > 0 ?
                    data.text.footerCustomLines.map(footer => `<p class="centrado">${footer}</p>`).join('') :
                    ''
                }
            </div>
        </div>
    </div>`;

        return template;
    }
