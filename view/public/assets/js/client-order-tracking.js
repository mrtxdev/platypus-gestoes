
document.addEventListener('DOMContentLoaded', () => {
    const trackingContainer = document.querySelector(
        '[data-order-tracking]'
    );

    if (!trackingContainer) {
        return;
    }

    const tableToken = trackingContainer.dataset.tableToken;

    if (!tableToken) {
        return;
    }

    const endpoint =
        '/platypus_gestoes/view/public/client/client.order.status.php';

    const statusInformation = {
        PENDING: {
            title: 'Pedido recebido!',
            description: 'Seu pedido foi enviado e está aguardando a cozinha.',
            step: 1,
            icon: '📥'
        },
        PREPARING: {
            title: 'Seu pedido está sendo preparado',
            description: 'A equipe da cozinha já começou a preparar seu pedido.',
            step: 2,
            icon: '👨‍🍳'
        },
        READY: {
            title: 'Seu pedido está pronto!',
            description: 'A cozinha finalizou seu pedido. Aguarde a entrega na sua mesa.',
            step: 3,
            icon: '🍽️'
        }
    };

    function createElement(tag, className, text = '') {
        const element = document.createElement(tag);

        if (className) {
            element.className = className;
        }

        element.textContent = text;

        return element;
    }

    function renderOrders(orders) {
        trackingContainer.replaceChildren();

        if (!orders.length) {
            const emptyState = createElement(
                'div',
                'tracking-empty'
            );

            emptyState.append(
                createElement('span', 'tracking-empty-icon', '🛒'),
                createElement('h2', '', 'Nenhum pedido por aqui'),
                createElement(
                    'p',
                    '',
                    'Quando você fizer um pedido, poderá acompanhar tudo nesta área.'
                )
            );

            trackingContainer.append(emptyState);

            return;
        }

        const heading = createElement(
            'div',
            'tracking-heading'
        );

        heading.append(
            createElement('span', 'tracking-heading-eyebrow', 'ACOMPANHAMENTO'),
            createElement('h2', '', 'Acompanhe seus pedidos')
        );

        trackingContainer.append(heading);

        orders.forEach(order => {
            const information = statusInformation[order.status];

            if (!information) {
                return;
            }

            const card = createElement(
                'article',
                'tracking-order-card'
            );

            const header = createElement(
                'div',
                'tracking-order-header'
            );

            const orderTitle = createElement(
                'div',
                'tracking-order-title'
            );

            orderTitle.append(
                createElement('span', 'tracking-order-label', `PEDIDO #${order.id}`),
                createElement('h3', '', information.title)
            );

            const icon = createElement(
                'span',
                'tracking-order-icon',
                information.icon
            );

            header.append(orderTitle, icon);

            const description = createElement(
                'p',
                'tracking-order-description',
                information.description
            );

            const progress = createElement(
                'div',
                'tracking-progress'
            );

            const steps = [
                'Recebido',
                'Preparando',
                'Pronto'
            ];

            steps.forEach((step, index) => {
                const stepNumber = index + 1;

                const stepElement = createElement(
                    'div',
                    'tracking-step'
                );

                if (stepNumber <= information.step) {
                    stepElement.classList.add('completed');
                }

                const marker = createElement(
                    'span',
                    'tracking-step-marker',
                    stepNumber <= information.step ? '✓' : String(stepNumber)
                );

                const label = createElement(
                    'span',
                    'tracking-step-label',
                    step
                );

                stepElement.append(marker, label);
                progress.append(stepElement);

                if (index < steps.length - 1) {
                    const connector = createElement(
                        'span',
                        'tracking-step-connector'
                    );

                    if (stepNumber < information.step) {
                        connector.classList.add('completed');
                    }

                    progress.append(connector);
                }
            });

            const itemsContainer = createElement(
                'div',
                'tracking-order-items'
            );

            const itemsTitle = createElement(
                'h4',
                '',
                'Itens do pedido'
            );

            itemsContainer.append(itemsTitle);

            (order.items || []).forEach(item => {
                const itemRow = createElement(
                    'div',
                    'tracking-order-item'
                );

                itemRow.append(
                    createElement(
                        'span',
                        '',
                        `${item.quantity}x ${item.product_name}`
                    )
                );

                itemsContainer.append(itemRow);
            });

            const footer = createElement(
                'div',
                'tracking-order-footer'
            );

            footer.append(
                createElement(
                    'span',
                    '',
                    'Total do pedido'
                ),
                createElement(
                    'strong',
                    '',
                    Number(order.total_amount).toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                    })
                )
            );

            card.append(
                header,
                description,
                progress,
                itemsContainer,
                footer
            );

            trackingContainer.append(card);
        });
    }

    async function updateTracking() {
        try {
            const response = await fetch(
                `${endpoint}?mesa=${encodeURIComponent(tableToken)}`,
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    cache: 'no-store'
                }
            );

            if (!response.ok) {
                throw new Error('Unable to retrieve orders.');
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message);
            }

            renderOrders(data.orders);

        } catch (error) {
            console.error('Order tracking error:', error);
        }
    }

    updateTracking();

    window.setInterval(updateTracking, 8000);
});
