<?php

require_once __DIR__ . '/../../../controller/kitchen.orders.controller.php';

/*
|--------------------------------------------------------------------------
| UPDATE TABLE ORDERS
|--------------------------------------------------------------------------
*/

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && ($_POST['action'] ?? '') === 'kitchen-update-status'
) {

    $result = kitchenUpdateOrderStatus();

    $_SESSION['kitchen_message'] = $result['message'];
    $_SESSION['kitchen_success'] = (bool) $result['success'];

    header(
        'Location: /platypus_gestoes/router.php?pagina=monitor-cozinha'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| LOAD GROUPED ORDERS
|--------------------------------------------------------------------------
*/

$orders = getKitchenOrders();

$message = $_SESSION['kitchen_message'] ?? null;
$success = $_SESSION['kitchen_success'] ?? false;

unset(
    $_SESSION['kitchen_message'],
    $_SESSION['kitchen_success']
);

$escape = static function ($value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
};

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link
        rel="stylesheet"
        href="/platypus_gestoes/view/public/assets/css/style.css?v=12"
    >

    <title>Platypus - Monitor da Cozinha</title>

</head>

<body>

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="kitchen-orders">

        <header class="kitchen-header">

            <span class="kitchen-eyebrow">
                OPERAÇÃO
            </span>

            <h1>Monitor da cozinha</h1>

            <p>
                Acompanhe os pedidos agrupados por mesa e atualize o andamento do preparo.
            </p>

        </header>

        <?php if ($message): ?>

            <div class="kitchen-message <?= $success ? 'success' : 'error' ?>">
                <?= $escape($message) ?>
            </div>

        <?php endif; ?>

        <?php if (empty($orders)): ?>

            <section class="kitchen-empty">

                <h2>Nenhum pedido pendente</h2>

                <p>
                    Os novos pedidos aparecerão aqui automaticamente após serem realizados.
                </p>

            </section>

        <?php else: ?>

            <section class="kitchen-list">

                <?php foreach ($orders as $order): ?>

                    <?php

                    $statusClass = strtolower($order['status']);

                    $statusLabel = match ($order['status']) {
                        'PENDING' => 'PENDENTE',
                        'PREPARING' => 'EM PREPARO',
                        'MIXED' => 'PARCIALMENTE EM PREPARO',
                        default => 'EM ANDAMENTO'
                    };

                    $nextStatus = $order['status'] === 'PREPARING'
                        ? 'READY'
                        : 'PREPARING';

                    $buttonLabel = $nextStatus === 'PREPARING'
                        ? 'Iniciar preparo'
                        : 'Marcar como pronto';

                    ?>

                    <article class="kitchen-card">

                        <header class="kitchen-card-header">

                            <div>

                                <h2>
                                    Mesa <?= $escape(
                                        $order['table_number'] ?? 'Não identificada'
                                    ) ?>
                                </h2>

                                <span class="kitchen-order-date">
                                    Primeiro pedido:
                                    <?= $escape(date(
                                        'd/m/Y H:i',
                                        strtotime($order['created_at'])
                                    )) ?>
                                </span>

                            </div>

                            <span class="kitchen-status <?= $escape($statusClass) ?>">
                                <?= $escape($statusLabel) ?>
                            </span>

                        </header>

                        <div class="kitchen-card-info">

                            <strong>
                                <?= (int) $order['order_count'] ?>
                                <?= $order['order_count'] === 1
                                    ? 'pedido'
                                    : 'pedidos' ?>
                            </strong>

                            <span class="kitchen-order-count">
                                <?= count($order['items']) ?>
                                <?= count($order['items']) === 1
                                    ? 'item'
                                    : 'itens' ?>
                            </span>

                        </div>

                        <div class="kitchen-items">

                            <?php foreach ($order['items'] as $item): ?>

                                <div class="kitchen-item">

                                    <span class="kitchen-item-name">
                                        <?= $escape($item['quantity']) ?>x
                                        <?= $escape($item['product_name']) ?>
                                    </span>

                                    <strong>
                                        R$ <?= number_format(
                                            (float) $item['unit_price']
                                                * (int) $item['quantity'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>
                                    </strong>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <div class="kitchen-total">

                            <span>Total da mesa</span>

                            <strong>
                                R$ <?= number_format(
                                    (float) $order['total_amount'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                            </strong>

                        </div>

                        <form
                            method="POST"
                            action="/platypus_gestoes/router.php?pagina=monitor-cozinha"
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="kitchen-update-status"
                            >

                            <input
                                type="hidden"
                                name="table_id"
                                value="<?= (int) $order['table_id'] ?>"
                            >

                            <input
                                type="hidden"
                                name="status"
                                value="<?= $escape($nextStatus) ?>"
                            >

                            <button type="submit" class="kitchen-button">
                                <?= $escape($buttonLabel) ?>
                            </button>

                        </form>

                    </article>

                <?php endforeach; ?>

            </section>

        <?php endif; ?>

    </main>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>

</body>
</html>
