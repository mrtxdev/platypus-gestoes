(() => {
    'use strict';

    const MIN_QUANTITY = 1;
    const MAX_QUANTITY = 99;
    const UPDATE_DELAY = 350;

    const timers = new WeakMap();
    const requests = new WeakMap();

    const currency = new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    function clamp(value) {
        const number = Number.parseInt(value, 10);
        if (!Number.isFinite(number)) return MIN_QUANTITY;
        return Math.max(MIN_QUANTITY, Math.min(MAX_QUANTITY, number));
    }

    function money(cents) {
        return `R$ ${currency.format(cents / 100)}`;
    }

    function updateMenuProductPrice(form) {
        if (!form) return;
        const product = form.closest('.client-product');
        const quantityInput = form.querySelector('input[name="quantity"]');
        if (!product || !quantityInput) return;

        const priceElement = product.querySelector('[data-unit-price]');
        const totalElement = product.querySelector('[data-product-total]');
        if (!priceElement || !totalElement) return;

        const unitPrice = Number(priceElement.dataset.unitPrice);
        if (!Number.isFinite(unitPrice) || unitPrice < 0) return;
        totalElement.textContent = currency.format(unitPrice * clamp(quantityInput.value));
    }

    function updateCartSummary(data) {
        const totalElement = document.querySelector('[data-cart-total]');
        const itemsElement = document.querySelector('[data-cart-total-items]');
        const labelElement = document.querySelector('[data-cart-items-label]');
        if (totalElement) totalElement.textContent = money(data.total_cents);
        if (itemsElement) itemsElement.textContent = data.total_items;
        if (labelElement) labelElement.textContent = data.total_items === 1 ? 'item' : 'itens';
    }

    function updateMenuCartIndicator(data) {
        const countElement = document.querySelector('[data-cart-count]');
        const indicator = document.querySelector('[data-cart-indicator]');
        if (!countElement) return;

        const totalItems = data.total_items;
        countElement.textContent = totalItems;
        if (indicator) {
            indicator.setAttribute(
                'aria-label',
                `${totalItems} ${totalItems === 1 ? 'item' : 'itens'} no carrinho. Abrir carrinho.`
            );
        }
    }

    function updateCartItems(data) {
        const productsContainer = document.querySelector('[data-cart-products]');
        const cartContent = document.querySelector('[data-cart-content]');
        const emptyMessage = document.querySelector('[data-cart-empty]');
        if (!productsContainer) return;

        const serverItems = new Map(data.items.map(item => [Number(item.id), item]));
        document.querySelectorAll('[data-cart-item]').forEach(element => {
            const itemId = Number(element.dataset.cartItemId);
            const serverItem = serverItems.get(itemId);
            if (!serverItem) {
                element.remove();
                return;
            }

            const input = element.querySelector('[data-cart-quantity]');
            const subtotal = element.querySelector('[data-item-subtotal]');
            if (input) {
                input.value = serverItem.quantity;
                input.dataset.savedQuantity = serverItem.quantity;
            }
            if (subtotal) subtotal.textContent = money(serverItem.subtotal_cents);

            const status = element.querySelector('[data-cart-status]');
            if (status) status.textContent = '';
        });

        const isEmpty = data.items.length === 0;
        if (cartContent) cartContent.hidden = isEmpty;
        if (emptyMessage) emptyMessage.hidden = !isEmpty;
        updateCartSummary(data);
    }

    function updateLocalDisplay() {
        const items = document.querySelectorAll('[data-cart-item]');
        let totalCents = 0;
        let totalItems = 0;

        items.forEach(item => {
            const input = item.querySelector('[data-cart-quantity]');
            if (!input) return;

            const quantity = clamp(input.value);
            const unitPrice = Number(item.dataset.unitPriceCents);
            const subtotal = quantity * unitPrice;
            totalCents += subtotal;
            totalItems += quantity;

            const subtotalElement = item.querySelector('[data-item-subtotal]');
            if (subtotalElement) subtotalElement.textContent = money(subtotal);
        });

        const totalElement = document.querySelector('[data-cart-total]');
        const itemsElement = document.querySelector('[data-cart-total-items]');
        const labelElement = document.querySelector('[data-cart-items-label]');
        if (totalElement) totalElement.textContent = money(totalCents);
        if (itemsElement) itemsElement.textContent = totalItems;
        if (labelElement) labelElement.textContent = totalItems === 1 ? 'item' : 'itens';
    }

    function setStatus(form, message) {
        const status = form.querySelector('[data-cart-status]');
        if (status) status.textContent = message;
    }

    function setProductStatus(form, message) {
        const status = form.querySelector('[data-product-status]');
        if (status) status.textContent = message;
    }

    function validateCartResponse(data) {
        if (
            !data || data.success !== true || !Array.isArray(data.items)
            || !Number.isSafeInteger(data.total_cents) || data.total_cents < 0
            || !Number.isSafeInteger(data.total_items) || data.total_items < 0
        ) return false;

        return data.items.every(item =>
            Number.isSafeInteger(Number(item.id))
            && Number.isSafeInteger(Number(item.quantity))
            && Number.isSafeInteger(Number(item.unit_price_cents))
            && Number.isSafeInteger(Number(item.subtotal_cents))
            && Number(item.quantity) >= MIN_QUANTITY
            && Number(item.quantity) <= MAX_QUANTITY
            && Number(item.unit_price_cents) >= 0
            && Number(item.subtotal_cents) >= 0
        );
    }

    async function sendCartRequest(form, quantity = null, action = null) {
        const formData = new FormData(form);
        if (quantity !== null) formData.set('quantity', String(clamp(quantity)));
        if (action !== null) formData.set('action', action);

        const actionUrl = form.getAttribute('action');
        if (!actionUrl) throw new Error('Não foi possível identificar a solicitação.');

        const url = new URL(actionUrl, window.location.href);
        if (url.origin !== window.location.origin) throw new Error('Solicitação inválida.');

        const response = await fetch(url.href, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            throw new Error('Não foi possível processar a resposta do servidor.');
        }

        let data;
        try {
            data = await response.json();
        } catch {
            throw new Error('Não foi possível processar a resposta do servidor.');
        }

        if (!response.ok || !validateCartResponse(data)) {
            throw new Error(data?.message || 'Não foi possível atualizar o carrinho. Tente novamente.');
        }
        return data;
    }

    async function addProductToCart(form, button) {
        if (requests.has(form)) return requests.get(form);
        const quantityInput = form.querySelector('input[name="quantity"]');
        if (!quantityInput) return;

        quantityInput.value = clamp(quantityInput.value);
        updateMenuProductPrice(form);
        if (button) button.disabled = true;

        const request = (async () => {
            try {
                setProductStatus(form, 'Adicionando...');
                const data = await sendCartRequest(form, quantityInput.value, 'add-to-cart');
                updateMenuCartIndicator(data);
                setProductStatus(form, 'Produto adicionado ao carrinho.');
            } catch (error) {
                setProductStatus(form, error.message || 'Não foi possível adicionar o produto. Tente novamente.');
            } finally {
                if (button && form.contains(button)) button.disabled = false;
                requests.delete(form);
            }
        })();

        requests.set(form, request);
        return request;
    }

    async function saveCartQuantity(form) {
        if (requests.has(form)) return requests.get(form);
        const input = form.querySelector('[data-cart-quantity]');
        if (!input) return;

        const savedQuantity = Number(input.dataset.savedQuantity);
        const requestedQuantity = clamp(input.value);
        if (requestedQuantity === savedQuantity) return;

        const request = (async () => {
            try {
                while (true) {
                    const quantity = clamp(input.value);
                    if (quantity === Number(input.dataset.savedQuantity)) break;
                    setStatus(form, 'Atualizando...');
                    const data = await sendCartRequest(form, quantity);
                    updateCartItems(data);
                    if (clamp(input.value) === Number(input.dataset.savedQuantity)) break;
                }
                setStatus(form, 'Atualizado');
            } catch (error) {
                input.value = input.dataset.savedQuantity;
                updateLocalDisplay();
                setStatus(form, error.message || 'Não foi possível atualizar a quantidade. Tente novamente.');
            } finally {
                requests.delete(form);
                if (clamp(input.value) !== Number(input.dataset.savedQuantity)) scheduleCartUpdate(form);
            }
        })();

        requests.set(form, request);
        return request;
    }

    function scheduleCartUpdate(form) {
        if (!form) return;
        const previousTimer = timers.get(form);
        if (previousTimer) clearTimeout(previousTimer);
        timers.set(form, setTimeout(() => saveCartQuantity(form), UPDATE_DELAY));
    }

    async function removeCartItem(form) {
        const button = form.querySelector('button[type="submit"]');
        if (button) button.disabled = true;
        try {
            setStatus(form, 'Removendo...');
            const data = await sendCartRequest(form);
            updateCartItems(data);
        } catch (error) {
            setStatus(form, error.message || 'Não foi possível remover o produto. Tente novamente.');
        } finally {
            if (button && document.body.contains(button)) button.disabled = false;
        }
    }

    document.addEventListener('click', event => {
        const addButton = event.target.closest('[data-cart-add-button]');
        if (addButton) {
            event.preventDefault();
            const form = addButton.closest('[data-add-to-cart-form]');
            if (form) addProductToCart(form, addButton);
            return;
        }

        const button = event.target.closest('[data-quantity-action], [data-cart-quantity-action]');
        if (!button) return;

        if (button.hasAttribute('data-quantity-action')) {
            event.preventDefault();
            const form = button.closest('[data-add-to-cart-form]');
            const input = button.closest('.client-quantity-selector')?.querySelector('input[name="quantity"]');
            if (!input || !form) return;
            const delta = button.dataset.quantityAction === 'increase' ? 1 : -1;
            input.value = clamp(Number(input.value) + delta);
            updateMenuProductPrice(form);
            return;
        }

        const form = button.closest('[data-cart-update-form]');
        const input = button.closest('.client-quantity-selector')?.querySelector('[data-cart-quantity]');
        if (!input || !form) return;
        event.preventDefault();
        const delta = button.dataset.cartQuantityAction === 'increase' ? 1 : -1;
        input.value = clamp(Number(input.value) + delta);
        updateLocalDisplay();
        scheduleCartUpdate(form);
    });

    document.addEventListener('input', event => {
        const input = event.target;
        if (input.matches('[data-add-to-cart-form] input[name="quantity"]')) {
            input.value = input.value.replace(/[^0-9]/g, '');
            if (input.value !== '') input.value = clamp(input.value);
            updateMenuProductPrice(input.closest('[data-add-to-cart-form]'));
            return;
        }
        if (!input.matches('[data-cart-quantity]')) return;
        input.value = input.value.replace(/[^0-9]/g, '');
        if (input.value !== '') input.value = clamp(input.value);
        updateLocalDisplay();
        scheduleCartUpdate(input.closest('[data-cart-update-form]'));
    });

    document.addEventListener('change', event => {
        const input = event.target;
        if (input.matches('[data-add-to-cart-form] input[name="quantity"]')) {
            input.value = clamp(input.value);
            updateMenuProductPrice(input.closest('[data-add-to-cart-form]'));
            return;
        }
        if (!input.matches('[data-cart-quantity]')) return;
        input.value = clamp(input.value);
        updateLocalDisplay();
        scheduleCartUpdate(input.closest('[data-cart-update-form]'));
    });

    document.addEventListener('submit', event => {
        const addForm = event.target.closest('[data-add-to-cart-form]');
        if (addForm) {
            if (event.submitter && event.submitter.value === 'order-now') return;
            event.preventDefault();
            const addButton = addForm.querySelector('[data-cart-add-button]');
            if (addButton) addProductToCart(addForm, addButton);
            return;
        }

        const updateForm = event.target.closest('[data-cart-update-form]');
        if (updateForm) {
            event.preventDefault();
            const timer = timers.get(updateForm);
            if (timer) clearTimeout(timer);
            saveCartQuantity(updateForm);
            return;
        }

        const removeForm = event.target.closest('[data-cart-remove-form]');
        if (removeForm) {
            event.preventDefault();
            removeCartItem(removeForm);
        }
    });

    document.querySelectorAll('[data-add-to-cart-form]').forEach(form => {
        const input = form.querySelector('input[name="quantity"]');
        if (input) {
            input.value = clamp(input.value);
            updateMenuProductPrice(form);
        }
    });

    document.querySelectorAll('[data-cart-quantity]').forEach(input => {
        input.dataset.savedQuantity = input.value;
    });
    updateLocalDisplay();
})();
