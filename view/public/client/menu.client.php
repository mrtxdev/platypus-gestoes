<?php
require_once __DIR__ . '/../../../controller/client.menu.controller.php';

$menuData = getClientMenuData();
$table = $menuData['table'];
$products = $menuData['products'];
$pageError = $menuData['error'];
$cartCount = max(0, (int) ($menuData['cartCount'] ?? 0));
$csrfToken = $_SESSION['csrf_token'] ?? '';
$tableToken = (string) ($table['qr_token'] ?? '');

$cartUrl = '/platypus_gestoes/router.php?pagina=carrinho';
if ($tableToken !== '' && preg_match('/\A[a-fA-F0-9]{36}\z/', $tableToken)) {
    $cartUrl .= '&mesa=' . rawurlencode($tableToken);
}

$orderMessage = $_SESSION['order_message'] ?? null;
$orderSuccess = $_SESSION['order_success'] ?? false;
unset($_SESSION['order_message'], $_SESSION['order_success']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Platypus - Cardápio Digital</title>
    <link rel="stylesheet" href="/platypus_gestoes/view/public/assets/css/style.css?v=13">
    <link rel="shortcut icon" href="/platypus_gestoes/view/public/assets/images/icons/favicon.ico" type="image/x-icon">
</head>
<body>
<main class="client-menu">
    <?php if ($pageError !== null): ?>
        <section class="card client-error">
            <?php if ($pageError === 'invalid'): ?>
                <h2>QR-Code inválido</h2>
                <p>Não foi possível localizar esta mesa. Solicite ajuda a um funcionário do restaurante.</p>
            <?php else: ?>
                <h2>Cardápio temporariamente indisponível</h2>
                <p>Não foi possível carregar os produtos neste momento. Tente novamente mais tarde ou solicite ajuda à equipe.</p>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <?php if ($orderMessage !== null): ?>
            <div class="card <?= $orderSuccess ? 'success-message' : 'error-message' ?>">
                <?= escapeHtml($orderMessage) ?>
            </div>
        <?php endif; ?>

        <header class="client-menu-header">
            <div class="client-menu-brand"><span class="client-menu-brand-icon">P</span> PLATYPUS</div>
            <span class="client-menu-eyebrow">UMA EXPERIÊNCIA DELICIOSA</span>
            <h1>Nosso cardápio</h1>
            <p>Escolha seus pratos favoritos e aproveite!</p>
            <div class="client-table"><span aria-hidden="true">⌖</span> <?= escapeHtml($table['table_number']) ?></div>
        </header>

        <section class="client-order-tracking"
                 data-order-tracking
                 data-table-token="<?= escapeHtml($tableToken) ?>"
                 aria-live="polite"
                 aria-label="Acompanhamento dos pedidos">
            <div class="tracking-loading"><span class="tracking-loading-icon">🍽️</span><p>Carregando seus pedidos...</p></div>
        </section>

        <?php if ($products === []): ?>
            <section class="card client-empty">
                <span class="client-empty-icon">🍽️</span>
                <h2>Cardápio vazio</h2>
                <p>Não há produtos disponíveis no momento.</p>
            </section>
        <?php else: ?>
            <section class="client-products">
                <?php foreach ($products as $product): ?>
                    <article class="card client-product">
                        <div class="client-product-image-wrapper">
                            <?php if ($product['imageUrl'] !== null): ?>
                                <img class="client-product-image"
                                     src="<?= escapeHtml($product['imageUrl']) ?>"
                                     alt="<?= escapeHtml($product['name']) ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="client-product-image client-product-no-image" role="img" aria-label="Imagem não cadastrada">🍽️</div>
                            <?php endif; ?>
                        </div>

                        <div class="client-product-body">
                            <h2 class="client-product-title"><?= escapeHtml($product['name']) ?></h2>
                            <p class="client-product-description"><?= escapeHtml($product['description'] ?? 'Produto sem descrição.') ?></p>

                            <div class="client-product-footer">
                                <div class="client-product-price" data-unit-price="<?= number_format((float) $product['price'], 2, '.', '') ?>">
                                    <span>R$</span>
                                    <span data-product-total><?= number_format((float) $product['price'], 2, ',', '.') ?></span>
                                </div>

                                <form class="client-product-form"
                                      data-add-to-cart-form
                                      action="/platypus_gestoes/router.php?pagina=cardapio"
                                      method="POST">
                                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= escapeHtml($csrfToken) ?>">
                                    <input type="hidden" name="table_token" value="<?= escapeHtml($tableToken) ?>">

                                    <?php if (!empty($product['requiredIngredients']) || !empty($product['optionalIngredients'])): ?>
                                        <section class="client-product-ingredients">
                                            <h3>Composição do prato</h3>
                                            <?php if (!empty($product['requiredIngredients'])): ?>
                                                <p><strong>Inclui:</strong>
                                                    <span class="client-required-list">
                                                        <?= escapeHtml(implode(', ', array_column($product['requiredIngredients'], 'name'))) ?>
                                                    </span>
                                                </p>
                                            <?php endif; ?>

                                            <?php if (!empty($product['optionalIngredients'])): ?>
                                                <p><strong>Opcionais:</strong> escolha o que deseja retirar.</p>
                                                <?php foreach ($product['optionalIngredients'] as $ingredient): ?>
                                                    <?php if ($ingredient['isActive']): ?>
                                                        <label class="client-optional-ingredient">
                                                            <input type="checkbox"
                                                                   name="removed_ingredients[]"
                                                                   value="<?= (int) $ingredient['id'] ?>">
                                                            <span>Sem <?= escapeHtml($ingredient['name']) ?></span>
                                                        </label>
                                                    <?php else: ?>
                                                        <p><?= escapeHtml($ingredient['name']) ?> (indisponível)</p>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                <small class="client-customization-help">Os ingredientes opcionais vêm no prato por padrão. Marque apenas os que deseja retirar.</small>
                                            <?php endif; ?>
                                        </section>
                                    <?php endif; ?>

                                    <div class="client-quantity-control">
                                        <label class="client-quantity-label">Quantidade</label>
                                        <div class="client-quantity-selector">
                                            <button type="button" class="client-quantity-button" data-quantity-action="decrease" aria-label="Diminuir quantidade">−</button>
                                            <input type="number" name="quantity" class="client-quantity-input" value="1" min="1" max="99" required aria-label="Quantidade do produto">
                                            <button type="button" class="client-quantity-button" data-quantity-action="increase" aria-label="Aumentar quantidade">+</button>
                                        </div>
                                    </div>

                                    <div class="client-product-actions">
                                        <button type="button" data-cart-add-button class="client-product-button client-add-button">
                                            <span aria-hidden="true">+</span> Adicionar
                                        </button>
                                        <button type="submit" name="action" value="order-now" class="client-product-button client-order-button">Pedir agora</button>
                                    </div>
                                    <span class="client-product-status" data-product-status aria-live="polite"></span>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <footer class="client-footer">
            <p>Preparado com carinho para você.</p>
            <p>Platypus &copy; <?= date('Y') ?></p>
        </footer>

        <a href="<?= escapeHtml($cartUrl) ?>" class="client-cart-indicator" data-cart-indicator
           aria-label="<?= $cartCount ?> <?= $cartCount === 1 ? 'item' : 'itens' ?> no carrinho. Abrir carrinho.">
            <span class="client-cart-emoji" aria-hidden="true">🛒</span>
            <span class="client-cart-label">Meu carrinho</span>
            <span class="client-cart-count" data-cart-count aria-live="polite"><?= $cartCount ?></span>
        </a>
    <?php endif; ?>
</main>
<script src="/platypus_gestoes/view/public/assets/js/client-cart.js?v=10" defer></script>
<script src="/platypus_gestoes/view/public/assets/js/client-order-tracking.js?v=1" defer></script>
</body>
</html>
