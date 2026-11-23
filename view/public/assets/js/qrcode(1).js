
document.addEventListener('DOMContentLoaded', () => {
    const qrElements = document.querySelectorAll('[data-qr]');

    qrElements.forEach((element) => {
        const url = element.dataset.qr;

        element.replaceChildren();

        if (typeof QRCode === 'undefined') {
            element.textContent = 'Biblioteca de QR-Code indisponível.';
            return;
        }

        try {
            new QRCode(element, {
                text: url,
                width: 200,
                height: 200,
                colorDark: '#111827',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        } catch (error) {
            console.error('QR-Code generation failed:', error);
            element.textContent = 'Não foi possível gerar o QR-Code.';
        }
    });

    document.querySelectorAll('[data-copy-qr]').forEach((button) => {
        button.addEventListener('click', async () => {
            const url = button.dataset.copyQr;
            const originalText = button.textContent;

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(url);
                } else {
                    const temporaryInput = document.createElement('textarea');

                    temporaryInput.value = url;
                    temporaryInput.style.position = 'fixed';
                    temporaryInput.style.opacity = '0';

                    document.body.appendChild(temporaryInput);
                    temporaryInput.select();

                    const copied = document.execCommand('copy');

                    temporaryInput.remove();

                    if (!copied) {
                        throw new Error('Copy operation failed.');
                    }
                }

                button.textContent = 'Link copiado!';
            } catch (error) {
                console.error('Link copy failed:', error);
                button.textContent = 'Erro ao copiar';
            }

            window.setTimeout(() => {
                button.textContent = originalText;
            }, 2000);
        });
    });

    document.querySelectorAll('[data-print-qr]').forEach((button) => {
        button.addEventListener('click', () => {
            const card = button.closest('.qr-card');
            const qrContainer = card?.querySelector('[data-qr]');

            if (!qrContainer) {
                return;
            }

            const canvas = qrContainer.querySelector('canvas');
            const image = qrContainer.querySelector('img');

            let imageSource = '';

            if (canvas) {
                imageSource = canvas.toDataURL('image/png');
            } else if (image) {
                imageSource = image.src;
            }

            if (!imageSource) {
                alert('O QR-Code ainda não foi gerado.');
                return;
            }

            const tableTitle = card.querySelector('.qr-card-title')
                ?.textContent.trim() || 'Mesa';

            const printWindow = window.open(
                '',
                '_blank',
                'width=600,height=700'
            );

            if (!printWindow) {
                alert('Permita a abertura de janelas pop-up para imprimir.');
                return;
            }

            const safeTitle = escapeHtml(tableTitle);

            printWindow.document.open();

            printWindow.document.write(`
                <!DOCTYPE html>
                <html lang="pt-BR">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <title>${safeTitle} - Platypus</title>

                    <style>
                        * {
                            box-sizing: border-box;
                        }

                        body {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            min-height: 90vh;
                            margin: 0;
                            padding: 2rem;
                            font-family: Arial, sans-serif;
                            text-align: center;
                        }

                        h1 {
                            margin-bottom: 1rem;
                            font-size: 2rem;
                        }

                        img {
                            width: 280px;
                            height: 280px;
                            object-fit: contain;
                        }

                        p {
                            max-width: 300px;
                            margin-top: 1rem;
                            font-size: 1rem;
                        }

                        @media print {
                            body {
                                min-height: 100vh;
                            }   </section>

        <?php else: ?>

            <section class="client-products">

                <?php foreach ($products as $product): ?>

                    <?php
                    $imageFile = (string) ($product['image'] ?? '');

                    $isImageValid = $imageFile !== ''
                        && basename($imageFile) === $imageFile
                        && is_file(
                            __DIR__ . '/../assets/images/products/' . $imageFile
                        );

                    $imageUrl = '/platypus_gestoes/view/public/assets/images/products/'
                        . rawurlencode($imageFile);
                    ?>

                    <article class="card client-product">

                        <?php if ($isImageValid): ?>

                            <img
                                class="client-product-image"
                                src="<?= escapeClientMenu($imageUrl) ?>"
                                alt="<?= escapeClientMenu($product['name']) ?>"
                                loading="lazy"
                            >

                        <?php else: ?>

                            <div
                                class="client-product-image"
                                role="img"
                                aria-label="Imagem não cadastrada"
                            >
                                Sem imagem
                            </div>

                        <?php endif; ?>

                        <div class="client-product-body">

                            <h2 class="client-product-title">
                                <?= escapeClientMenu($product['name']) ?>
                            </h2>

                            <?php if (!empty($product['description'])): ?>

                                <p class="client-product-description">
                                    <?= escapeClientMenu($product['description']) ?>
                                </p>   </section>

        <?php else: ?>

            <section class="client-products">

                <?php foreach ($products as $product): ?>

                    <?php
                    $imageFile = (string) ($product['image'] ?? '');

                    $isImageValid = $imageFile !== ''
                        && basename($imageFile) === $imageFile
                        && is_file(
                            __DIR__ . '/../assets/images/products/' . $imageFile
                        );

                    $imageUrl = '/platypus_gestoes/view/public/assets/images/products/'
                        . rawurlencode($imageFile);
                    ?>

                    <article class="card client-product">

                        <?php if ($isImageValid): ?>

                            <img
                                class="client-product-image"
                                src="<?= escapeClientMenu($imageUrl) ?>"
                                alt="<?= escapeClientMenu($product['name']) ?>"
                                loading="lazy"
                            >

                        <?php else: ?>

                            <div
                                class="client-product-image"
                                role="img"
                                aria-label="Imagem não cadastrada"
                            >
                                Sem imagem
                            </div>

                        <?php endif; ?>

                        <div class="client-product-body">

                            <h2 class="client-product-title">
                                <?= escapeClientMenu($product['name']) ?>
                            </h2>

                            <?php if (!empty($product['description'])): ?>

                                <p class="client-product-description">
                                    <?= escapeClientMenu($product['description']) ?>
                                </p>

                            <?php else: ?>

                                <p class="client-product-description">
                                    Produto sem descrição.
                                </p>

                            <?php endif; ?>

                            <div class="client-product-price">
                                R$ <?= number_format(
                                    (float) $product['price'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </section>

        <?php endif; ?>

        <footer class="client-footer">
            <p>
                Para realizar seu pedido, solicite atendimento à equipe.
            </p>
            <p>Platypus &copy; <?= date('Y') ?></p>
        </footer>

    <?php endif; ?>

</main>

</body>
</html>


                            <?php else: ?>

                                <p class="client-product-description">
                                    Produto sem descrição.
                                </p>

                            <?php endif; ?>

                            <div class="client-product-price">
                                R$ <?= number_format(
                                    (float) $product['price'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </section>

        <?php endif; ?>

        <footer class="client-footer">
            <p>
                Para realizar seu pedido, solicite atendimento à equipe.
            </p>
            <p>Platypus &copy; <?= date('Y') ?></p>
        </footer>

    <?php endif; ?>

</main>

</body>
</html>

                        }
                    </style>
                </head>

                <body>
                    <h1>${safeTitle}</h1>
                    <img src="${imageSource}" alt="QR-Code da mesa">
                    <p>Escaneie o QR-Code para acessar o cardápio digital.</p>
                </body>
                </html>
            `);

            printWindow.document.close();

            printWindow.onload = () => {
                printWindow.focus();
                printWindow.print();
            };
        });
    });

    document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.dataset.confirmDelete;

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
});

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (character) => {
        const entities = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };

        return entities[character];
    });
}
