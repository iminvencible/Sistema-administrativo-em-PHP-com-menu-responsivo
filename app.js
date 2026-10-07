'use strict';

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
});

const printButton = document.getElementById('print-report');
if (printButton) {
    printButton.hidden = false;
    printButton.addEventListener('click', () => window.print());
}

const stockSelect = document.querySelector('[data-stock-select]');
if (stockSelect) {
    stockSelect.addEventListener('change', () => {
        const selected = stockSelect.selectedOptions[0];
        if (selected?.dataset.minimo !== undefined) {
            document.getElementById('quantidade_minima').value = selected.dataset.minimo;
        }
    });
}

const saleForm = document.getElementById('sale-form');
if (saleForm) {
    const items = document.getElementById('sale-items');
    const addButton = document.getElementById('add-item');
    const template = document.getElementById('sale-item-template');
    const labor = document.getElementById('mao_obra');
    const total = document.getElementById('sale-total');
    const client = document.getElementById('venda_cliente');
    const order = document.getElementById('ordem_servico_id');
    let sequence = items.children.length;
    const currency = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

    function updateTotal() {
        let amount = Math.round(Number(labor.value || 0) * 100);
        items.querySelectorAll('.sale-item').forEach((row) => {
            const price = Number(row.querySelector('[data-sale-product]').selectedOptions[0]?.dataset.price || 0);
            const quantity = Number(row.querySelector('[data-sale-quantity]').value || 0);
            amount += price * quantity;
        });
        total.value = 'Total estimado: ' + currency.format(amount / 100);
        addButton.disabled = items.children.length >= 30;
        items.querySelectorAll('.remove-item').forEach((button) => {
            button.disabled = items.children.length <= 1;
        });
    }

    function filterOrders() {
        Array.from(order.options).forEach((option) => {
            option.disabled = Boolean(option.value && option.dataset.cliente !== client.value);
        });
        if (order.selectedOptions[0]?.disabled) order.value = '';
    }

    addButton.hidden = false;
    addButton.addEventListener('click', () => {
        if (items.children.length >= 30) return;
        const fragment = template.content.cloneNode(true);
        const number = sequence++;
        const productId = 'sale-product-' + number;
        const quantityId = 'sale-quantity-' + number;
        fragment.querySelector('[data-sale-product]').id = productId;
        fragment.querySelector('[data-sale-quantity]').id = quantityId;
        const productLabel = fragment.querySelector('[data-product-label]');
        productLabel.htmlFor = productId;
        productLabel.textContent = 'Peça ' + (number + 1);
        const quantityLabel = fragment.querySelector('[data-quantity-label]');
        quantityLabel.htmlFor = quantityId;
        quantityLabel.textContent = 'Quantidade ' + (number + 1);
        fragment.querySelector('.remove-item').setAttribute('aria-label', 'Remover peça ' + (number + 1));
        items.appendChild(fragment);
        updateTotal();
        document.getElementById(productId).focus();
    });
    items.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-item');
        if (button && items.children.length > 1) {
            button.closest('.sale-item').remove();
            addButton.focus();
            updateTotal();
        }
    });
    saleForm.addEventListener('input', updateTotal);
    saleForm.addEventListener('change', updateTotal);
    client.addEventListener('change', filterOrders);
    filterOrders();
    updateTotal();
}
