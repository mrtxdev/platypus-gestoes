<?php
$tableId = (int) ($_SESSION['table_id'] ?? 0);
$cartItems = $cartItems ?? [];
$csrfToken = $_SESSION['csrf_token'] ?? '';

$cartMessage = $_SESSION['cart_message'] ?? null;
$cartSuccess = (bool) ($_SESSION['cart_success'] ?? false);
unset($_SESSION['cart_message'], $_SESSION['cart_success']);

$tableToken = '';
if ($tableId > 0) {
    try {
        $database = getDatabaseConnection();
        $statement = $database->prepare(
            "SELECT qr_token FROM dining_tables
             WHERE id = :table_id AND status <> 'INACTIVE' LIMIT 1"
        );
        $statement->execute(['table_id' => $tableId]);
        $tableToken = (string) ($statement->fetchColumn() ?: '');
    } catch (Throwable $exception) {
        error_log('Cart table token lookup failed: ' . $exception->getMessage());
    }
}

$menuUrl = '/platypus_gestoes/router.php?pagina=cardapio';
if ($tableToken !== '') {
    $menuUrl .= '&mesa=' . rawurlencode($tableToken);
}
$escapedMenuUrl = htmlspecialchars($menuUrl, ENT_QUOTES, 'UTF-8');
$totalCents = 0;
$totalItems = 0;
foreach ($cartItems as $item) {
    $quantity = (int) $item['quantity'];
    $unitPriceCents = (int) round((float) $item['unitPrice'] * 100);
    $totalCents += $unitPriceCents * $quantity;
    $totalItems += $quantity;
}
$hasCartItems = !empty($cartItems);
$h = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Platypus - Carrinho Digital</title>
    <link rel="stylesheet" href="/platypus_gestoes/view/public/assets/css/style.css?v=12">
    <link rel="shortcut icon" href="/platypus_gestoes/view/public/assets/images/icons/favicon.ico" type="image/x-icon">
</head>
<body>
<main class="cart-container">
    <header class="cart-header">
        <h1>Meu carrinho</h1>
        <p>Confira seus produtos antes de finalizar o pedido.</p>
    </header>

    <?php if ($cartMessage): ?>
        <div class="cart-message <?= $cartSuccess ? 'cart-message-success' : 'cart-message-error' ?>" role="status">
            <?= $h($cartMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($tableId <= 0): ?>
        <section class="cart-empty">
            <h2>Mesa não identificada</h2>
            <p>Acesse o cardápio pelo QR Code da sua mesa para utilizar o carrinho.</p>
        </section>
    <?php else: ?>
        <section class="cart-empty" data-cart-empty <?= $hasCartItems ? 'hidden' : '' ?>>
            <h2>Seu carrinho está vazio</h2>
            <p>Adicione produtos pelo cardápio para começar seu pedido.</p>
            <a href="<?= $escapedMenuUrl ?>">Voltar ao cardápio</a>
        </section>

        <section class="cart-content" data-cart-content <?= !$hasCartItems ? 'hidden' : '' ?>>
            <div class="cart-products" data-cart-products>
                <?php foreach ($cartItems as $item): ?>
                    <?php
                    $itemId = (int) $item['id'];
                    $itemName = (string) ($item['productName'] ?? 'Produto');
                    $quantity = (int) $item['quantity'];
                    $unitPriceCents = (int) round((float) $item['unitPrice'] * 100);
                    $subtotalCents = $unitPriceCents * $quantity;
                    $removedNames = $item['removedIngredientNames'] ?? [];
                    ?>
                    <article class="cart-item" data-cart-item data-cart-item-id="<?= $itemId ?>" data-unit-price-cents="<?= $unitPriceCents ?>">
                        <div class="cart-item-info">
                            <h2><?= $h($itemName) ?></h2>
                            <p>R$ <?= number_format($unitPriceCents / 100, 2, ',', '.') ?> por unidade</p>
                            <?php if ($removedNames !== []): ?>
                                <p class="cart-item-customization"><strong>Sem:</strong> <?= $h(implode(', ', $removedNames)) ?></p>
                            <?php endif; ?>
                            <strong>Subtotal: <span data-item-subtotal>R$ <?= number_format($subtotalCents / 100, 2, ',', '.') ?></span></strong>
                        </div>

                        <div class="cart-item-actions">
                            <form method="POST" action="/platypus_gestoes/router.php?pagina=carrinho" data-cart-update-form>
                                <input type="hidden" name="action" value="update-cart">
                                <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
                                <input type="hidden" name="cart_item_id" value="<?= $itemId ?>">
                                <label>Quantidade
                                    <span class="client-quantity-selector">
                                        <button type="button" class="client-quantity-button" data-cart-quantity-action="decrease" aria-label="Diminuir quantidade">−</button>
                                        <input type="number" name="quantity" value="<?= $quantity ?>" min="1" max="99" required data-cart-quantity class="client-quantity-input">
                                        <button type="button" class="client-quantity-button" data-cart-quantity-action="increase" aria-label="Aumentar quantidade">+</button>
                                    </span>
                                </label>
                                <span class="cart-item-status" data-cart-status aria-live="polite"></span>
                            </form>

                            <form method="POST" action="/platypus_gestoes/router.php?pagina=carrinho" data-cart-remove-form>
                                <input type="hidden" name="action" value="remove-cart">
                                <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
                                <input type="hidden" name="cart_item_id" value="<?= $itemId ?>">
                                <button type="submit">Remover</button>
                                <span class="cart-item-status" data-cart-status aria-live="polite"></span>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <aside class="cart-summary">
                <h2>Resumo do pedido</h2>
                <div class="cart-summary-total">
                    <span>Total</span>
                    <strong data-cart-total>R$ <?= number_format($totalCents / 100, 2, ',', '.') ?></strong>
                </div>
                <p class="cart-summary-count">
                    <span data-cart-total-items><?= $totalItems ?></span>
                    <span data-cart-items-label><?= $totalItems === 1 ? 'item' : 'itens' ?></span>
                </p>
                <form method="POST" action="/platypus_gestoes/router.php?pagina=carrinho" onsubmit="return confirm('Deseja enviar o pedido para a cozinha?');">
                    <input type="hidden" name="action" value="checkout-cart">
                    <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
                    <button type="submit">Finalizar pedido</button>
                </form>
                <a href="<?= $escapedMenuUrl ?>">Continuar escolhendo</a>
            </aside>
        </section>
    <?php endif; ?>
</main>
<script src="/platypus_gestoes/view/public/assets/js/client-cart.js?v=10" defer></script>
</body>
</html>
