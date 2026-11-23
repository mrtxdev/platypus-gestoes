<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/platypus_gestoes/view/public/assets/css/style.css?v=12">

    <title>Platypus - Pedidos Prontos</title>
</head>
<body>

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="ready-orders">

        <header class="ready-orders-header">
            <div>
                <span class="ready-orders-eyebrow">ATENDIMENTO</span>
                <h1>Pedidos prontos</h1>
                <p>Pedidos finalizados pela cozinha e aguardando entrega.</p>
            </div>
        </header>

        <section class="ready-orders-list">

            <?php if (empty($orders)): ?>

                <div class="ready-orders-empty">
                    <h2>Nenhum pedido pronto</h2>
                    <p>Quando a cozinha finalizar um pedido, ele aparecerá aqui.</p>
                </div>

            <?php else: ?>

                <?php foreach ($orders as $order): ?>

                    <article class="ready-order-card">

                        <div class="ready-order-info">
                            <span class="ready-order-number">
                                Pedido #<?= htmlspecialchars((string) $order['id'], ENT_QUOTES, 'UTF-8') ?>
                            </span>

                            <h2>
                                Mesa <?= htmlspecialchars((string) ($order['table_number'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>
                            </h2>

                            <span class="ready-order-status">
                                Pronto para entrega
                            </span>
                        </div>

                        <div class="ready-order-items">

                            <?php foreach ($order['items'] as $item): ?>

                                <p>
                                    <?= htmlspecialchars((string) $item['quantity'], ENT_QUOTES, 'UTF-8') ?>x
                                    <?= htmlspecialchars((string) $item['product_name'], ENT_QUOTES, 'UTF-8') ?>
                                </p>

                            <?php endforeach; ?>

                        </div>

                        <div class="ready-order-total">
                            <span>Total</span>

                            <strong>
                                R$ <?= number_format((float) $order['total_amount'], 2, ',', '.') ?>
                            </strong>
                        </div>

                        <form method="POST" action="/platypus_gestoes/router.php?pagina=pedidos-prontos">

                            <input
                                type="hidden"
                                name="order_id"
                                value="<?= htmlspecialchars((string) $order['id'], ENT_QUOTES, 'UTF-8') ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="deliver-order"
                            >

                            <button type="submit" class="ready-order-button">
                                Marcar como entregue
                            </button>

                        </form>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>

    </main>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>

</body>
</html>
