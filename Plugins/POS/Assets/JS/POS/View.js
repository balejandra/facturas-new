import Modals from "./components/Modals.js";
import Templates from "./components/Templates.js";
import {getElement} from "./Core.js";
import * as Money from "./Money.js";

export const cart = () => {
    return Object.freeze(new Cart());
}

export const checkout = () => {
    return Object.freeze(new Checkout());
}

export const main = () => {
    return Object.freeze(new Main());
}

export const modals = () => {
    return Modals;
}

export const templates = () => {
    return Templates;
}

const cartElements = {
    cartTotalLabel: getElement('cartTotal'),
    orderDiscountAmountLabel: getElement('orderDiscountAmountLabel'),
    orderDiscountAmountInput: getElement('orderDiscountAmountInput'),
    orderHoldButton: getElement('orderHoldButton'),
    orderItemsNumberLabel: getElement('orderItemsNumber'),
    orderNetoLabel: getElement('orderTotalNet'),
    orderTaxesLabel: getElement('orderTaxes'),
    orderTotalLabel: getElement('orderTotal'),
    productQuantityInput: getElement('productQuantityInput')
}

class Cart {
    cartTotalLabel = () => cartElements['cartTotalLabel'];
    orderDiscountAmountLabel = () => cartElements['orderDiscountAmountLabel'];
    orderDiscountAmountInput = () => cartElements['orderDiscountAmountInput'];
    orderHoldButton = () => cartElements['orderHoldButton'];
    orderItemsNumberLabel = () => cartElements['orderItemsNumberLabel'];
    orderNetoLabel = () => cartElements['orderNetoLabel'];
    productQuantityInput = () => cartElements['productQuantityInput'];

    showQuantityEditModal = ({index, cantidad}) => {
        cart().productQuantityInput().dataset.index = index;
        cart().productQuantityInput().value = cantidad;
        modals().productQuantityEditModal().show();
    };

    showProductEditModal = (product = {}) => {
        templates().renderCartEdit(product);
        modals().productEditModal().show();
    };

    updateLinesView = (product = {}) => {
        templates().renderCartEdit(product);
    };

    updateView = (data = {}) => {
        cart().cartTotalLabel().textContent = Money.roundFixed(data.doc.total);
        cart().orderItemsNumberLabel().textContent = Money.roundFixed(data.count);
        cart().orderDiscountAmountInput().value = data.doc.dtopor1 || 0;
        cart().orderDiscountAmountLabel().textContent = Money.roundFixed(data.getDiscountAmount());
        cart().orderNetoLabel().textContent = Money.roundFixed(data.doc.neto);

        templates().renderCartList(data);
    };
}

const checkoutElements = {
    'confirmOrderButton': getElement('orderSaveButton'),
    'changeAmountLabel': getElement('checkoutChangeAmount'),
    'tenderedAmountLabel': getElement('checkoutTenderedAmount'),
    'totalAmountLabel': getElement('checkoutTotal'),
    'changeAmountExchangeLabel': getElement('checkoutChangeAmountExchange'),
    'tenderedAmountExchangeLabel': getElement('checkoutTenderedAmountExchange'),
    'totalAmountExchangeLabel': getElement('checkoutTotalExchange'),
    'paymentApplyButton': getElement('paymentApplyButton'),
    'paymentApplyInput': getElement('paymentApplyInput'),
}

class Checkout {
    confirmOrderButton = () => checkoutElements['confirmOrderButton'];
    changeAmountLabel = () => checkoutElements['changeAmountLabel'];
    tenderedAmountLabel = () => checkoutElements['tenderedAmountLabel'];
    totalAmountLabel = () => checkoutElements['totalAmountLabel'];
    changeAmountExchangeLabel = () => checkoutElements['changeAmountExchangeLabel'];
    tenderedAmountExchangeLabel = () => checkoutElements['tenderedAmountExchangeLabel'];
    totalAmountExchangeLabel = () => checkoutElements['totalAmountExchangeLabel'];
    paymentApplyButton = () => checkoutElements['paymentApplyButton'];
    paymentAmountInput = () => checkoutElements['paymentApplyInput'];

    enableConfirmButton = (enable = true) => {
        checkout().confirmOrderButton().disabled = !enable;
    };

    getCurrentPaymentValue = () => parseFloat(checkout().paymentAmountInput().value) || 0;

    getCurrentPaymentData = ({code, description}) => ({
        amount: checkout().paymentAmountInput().value,
        description: description,
        method: code
    });

    updateView = (data) => {
        checkout().totalAmountLabel().textContent = data.total;
        checkout().totalAmountExchangeLabel().textContent = Money.roundFixed(
			data.total * AppSettings.tasacambio.importetasa
		);
        checkout().tenderedAmountLabel().textContent = data.getPaymentsTotal();
        checkout().tenderedAmountExchangeLabel().textContent = Money.roundFixed(
			data.getPaymentsTotal() * AppSettings.tasacambio.importetasa
		);
        checkout().changeAmountLabel().textContent = data.change;
        checkout().changeAmountExchangeLabel().textContent = Money.roundFixed(
			data.change * AppSettings.tasacambio.importetasa
		);

        templates().renderPaymentList(data);

        checkout().enableConfirmButton(data.change >= 0 && data.total !== 0);
    };

    showPaymentModal = (data = {}) => {
        checkout().paymentAmountInput().dataset.method = data.code;
        checkout().paymentAmountInput().dataset.description = data.description;
        modals().paymentModal().show();
    };
}

const mainElements = {
    cashMovmentForm: getElement('cashMovmentForm'),
    closeSessionForm: getElement('closeSessionForm'),
    customerNameLabel: getElement('customerNameLabel'),
    customerSearchBox: getElement('customerSearchBox'),
    documentFieldList: document.querySelectorAll('[data-document-field]'),
    documentNamelLabel: getElement('documentTypeLabel'),
    mainContent: getElement('mainContent'),
    newCustomerSaveButton: getElement('newCustomerSaveButton'),
    productSearchBox: getElement('productSearchBox')
}

class Main {
    customerNameLabel = () => mainElements['customerNameLabel'];
    customerSearchBox = () => mainElements['customerSearchBox'];
    cashMovmentForm = () => mainElements['cashMovmentForm'];
    closeSessionForm = () => mainElements['closeSessionForm'];
    productSearchBox = () => mainElements['productSearchBox'];
    newCustomerSaveButton = () => mainElements['newCustomerSaveButton'];
    updateCustomerNameLabel = (name = '') => {
        mainElements['customerNameLabel'].textContent = name;
    };
    updateDocumentNameLabel = (name = '') => {
        mainElements['documentNamelLabel'].textContent = name;
    };
    updateCustomerListView = (data = []) => {
        templates().renderCustomerList({ items: data });
    };
    updateLastOrdersList = (data = []) => {
        templates().renderLastOrderList({ items: data });
    };
    updatePausedOrdersList = (data = []) => {
        templates().renderPausedOrderList({ items: data });
    };
    updateProductFamilyList = (data = []) => {
        templates().renderProductFamilyList({ items: data });
    };
    updateProductSearchResult = (data = []) => {
        templates().renderProductSearchList({ items: data });
    };
    updateView = ({ doc }) => {
        const documentFields = mainElements['documentFieldList'];

        for (let i = 0; i < documentFields.length; i++) {
            updateDocumentFieldValue(doc, documentFields[i])
        }
    };
    showLastOrdersModal = function (data) {
        modals().lastOrdersModal().show();
        templates().renderLastOrderList({ items: data });
    }
    showPausedOrdersModal = function (data) {
        modals().pausedOrdersModal().show();
        templates().renderPausedOrderList({ items: data });
    }
    showProductImagesModal = function (data) {
        modals().productImagesModal().show();
        templates().renderProductImageList({ items: data });
    }
    showProductStockDetailModal = function (data) {
        modals().stockDetailModal().show();
        templates().renderProductStockList({ items: data });
    }
    showTicketPrint = function (data) {
        modals().ticketPrintModal().show();
        console.log(data);

        templates().renderTicketPrint({ items: data });
        let contenidoDiv = document.getElementById("ticketPrintingArea").innerHTML;

        // Abrir la ventana de impresión y escribir el contenido del div en ella
        let ventanaPrinting = window.open("", "blank");
        ventanaPrinting.document.write(
            "<html><head>" +
            "<style>" +
            ".ticket,.printer-ticket{font-family:Tahoma,Geneva,sans-serif;font-size:12px;}.linea-divisora{border-bottom:1px solid black;padding-bottom:10px;}.centrado{text-align:center;align-content:center;}.text-center{text-align:center;}.printer-ticket{display:table !important;width:100%;font-weight:light;line-height:1.3em;}.printer-ticket,.printer-ticket *{background-color:#fff;}.printer-ticket th:nth-child(1),.printer-ticket td:nth-child(1){width:50px;}.printer-ticket th:nth-child(3){width:90px;}.printer-ticket td:nth-child(3),.printer-ticket td:nth-child(4){width:90px;text-align:right;}.printer-ticket th{font-weight:inherit;padding:10px 0;text-align:center;border-bottom:1px dashed #BCBCBC;}.printer-ticket tbody tr:last-child td{padding-bottom:10px;}.printer-ticket tfoot .sup td{padding:10px 0;border-top:1px dashed #BCBCBC;}.printer-ticket tfoot .sup.p--0 td{padding-bottom:0;}.printer-ticket .top td{padding-top:10px;}.printer-ticket .last td{padding-bottom:10px;}" +
            "</style></head><body>" +
            contenidoDiv +
            "</body></html > "
        );

        // Imprimir la ventana de impresión
        ventanaPrinting.print();
        ventanaPrinting.close();
        modals().ticketPrintModal().hide();
    };
}

/**
 * @param {HTMLElement} element
 */
export function toggleCollapse(element) {
    const target = getElement(element.dataset.target);
    const elementOntoggle = getElement(element.dataset.ontoggle);

    target.classList.toggle('hidden');

    if (elementOntoggle) {
        elementOntoggle.classList.toggle('hidden');
    }
}

/**
 * @param {HTMLElement} element
 */
const toggle = element => {
    let target = getElement(element.dataset.target);

    if (!target) return;

    target.classList.toggle('hidden');

    if (element.dataset.ontoggle) {
        getElement(element.dataset.ontoggle).classList.toggle('hidden');
    }
};

const updateDocumentFieldValue = (data = {}, element) => {
    const field = element.getAttribute('data-document-field');
    const format = element.getAttribute('data-format');

    switch (element.type) {
        case 'text':
        case 'textarea':
            element.value = data[field];
            break
        case 'number':
        case 'decimal':
            element.value = Money.roundFixed(data[field]);
            break;
        case'checkbox':
            element.checked = data[field] === true || data[field] === "true";
            break;
        default:
            element.textContent = (format === 'number') ? Money.roundFixed(data[field]): data[field];
    }
}

/**
 * @param {HTMLElement} element
 */
const eventHandler = element => {
    const target = getElement(element.dataset.target);

    switch (element.dataset.toggle) {
        case 'modal':
            modals().toggleModal(target)
            break;
        case 'collapse':
            toggleCollapse(element);
            break;
        default:
            toggle(element);
    }
};

document.addEventListener('click', function (event) {
    if (event.target.attributes.getNamedItem('data-toggle')) {
        eventHandler(event.target);
        event.stopPropagation();
    }
}, false);

/*window.addEventListener("click", function (event) {
    let menu = getElement('navbarMenu');

    /!*if (!menu.contains(event.target) && !menu.classList.contains('hidden')) {
        menu.classList.add('hidden');
    }*!/
})*/
