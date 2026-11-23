<?php

$reportsData = $reportsData ?? [];

$dates = $reportsData['dates'] ?? [
    'start_date' => date('Y-m-01'),
    'end_date' => date('Y-m-d')
];

$orders = $reportsData['orders'] ?? [];
$products = $reportsData['products'] ?? [];
$expenses = $reportsData['expenses'] ?? [];
$stockMovements = $reportsData['stock_movements'] ?? [];

$escape = static function ($value): string {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
};

$money = static function ($value): string {
    return 'R$ ' . number_format(
        (float) ($value ?? 0),
        2,
        ',',
        '.'
    );
};

$statusLabels = [
    'PENDING' => 'Pendente',
    'PREPARING' => 'Em preparação',
    'READY' => 'Pronto',
    'DELIVERED' => 'Entregue',
    'CANCELLED' => 'Cancelado'
];

$movementLabels = [
    'IN' => 'Entrada',
    'OUT' => 'Saída',
    'ADJUSTMENT' => 'Ajuste'
];

$flashMessage = $_SESSION['reports_message'] ?? '';
$flashSuccess = $_SESSION['reports_success'] ?? false;

unset(
    $_SESSION['reports_message'],
    $_SESSION['reports_success']
);

$exportUrl = static function (string $type) use ($dates): string {
    return '/platypus_gestoes/router.php?' . http_build_query([
        'pagina' => 'relatorios',
        'action' => 'export-csv',
        'type' => $type,
        'start_date' => $dates['start_date'],
        'end_date' => $dates['end_date']
    ]);
};

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Platypus - Relatórios</title>

    <link
        rel="stylesheet"
        href="/platypus_gestoes/view/public/assets/css/style.css?v=12"
    >

</head>

<body>

<?php require __DIR__ . '/../../includes/header.php'; ?>

<main class="reports-container">

    <section class="reports-heading">

        <div>

            <span class="reports-eyebrow">GESTÃO DO RESTAURANTE</span>

            <h1>Relatórios</h1>

            <p>
                Acompanhe o desempenho financeiro e operacional
                do seu restaurante.
            </p>

        </div>

        <div class="reports-heading-icon">
            <span>▤</span>
        </div>

    </section>

    <?php if ($flashMessage !== ''): ?>

        <div class="reports-alert <?= $flashSuccess ? 'success' : 'error' ?>">
            <?= $escape($flashMessage) ?>
        </div>

    <?php endif; ?>

    <!-- DATE FILTER -->

    <section class="reports-filter-card">

        <form
            method="GET"
            action="/platypus_gestoes/router.php"
            class="reports-filter-form"
        >

            <input
                type="hidden"
                name="pagina"
                value="relatorios"
            >

            <div class="reports-filter-field">

                <label for="start_date">Data inicial</label>

                <input
                    type="date"
                    id="start_date"
                    name="start_date"
                    value="<?= $escape($dates['start_date']) ?>"
                    required
                >

            </div>

            <div class="reports-filter-field">

                <label for="end_date">Data final</label>

                <input
                    type="date"
                    id="end_date"
                    name="end_date"
                    value="<?= $escape($dates['end_date']) ?>"
                    required
                >

            </div>

            <button type="submit" class="reports-button primary">
                Aplicar período
            </button>

        </form>

        <p class="reports-filter-description">
            Os indicadores e tabelas abaixo consideram o período selecionado.
        </p>

    </section>

    <section class="reports-section">

        <div class="reports-section-heading">

            <div>

                <span class="reports-eyebrow">PEDIDOS</span>

                <h2>Pedidos por situação</h2>

            </div>

        </div>

        <div class="reports-subsection">

            <div class="reports-status-grid">

                <?php if (empty($orders)): ?>

                    <p class="reports-empty">
                        Nenhum pedido registrado no período.
                    </p>

                <?php else: ?>

                    <?php foreach ($orders as $order): ?>

                        <div class="reports-status-item">

                            <span>
                                <?= $escape(
                                    $statusLabels[$order['status']]
                                    ?? $order['status']
                                ) ?>
                            </span>

                            <strong>
                                <?= (int) $order['total'] ?>
                            </strong>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>

    </section>

    <!-- PRODUCTS -->

    <section class="reports-section">

        <div class="reports-section-heading">

            <div>

                <span class="reports-eyebrow">CARDÁPIO</span>

                <h2>Produtos vendidos</h2>

            </div>

            <a
                href="<?= $escape($exportUrl('products')) ?>"
                class="reports-button secondary"
            >
                Exportar CSV
            </a>

        </div>

        <div class="reports-table-wrapper">

            <table class="reports-table">

                <thead>

                    <tr>
                        <th>Produto</th>
                        <th>Quantidade vendida</th>
                        <th>Faturamento dos itens</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (empty($products)): ?>

                        <tr>

                            <td colspan="3" class="reports-empty">
                                Nenhum produto vendido no período.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($products as $product): ?>

                            <tr>

                                <td>

                                    <strong>
                                        <?= $escape($product['product_name']) ?>
                                    </strong>

                                </td>

                                <td>
                                    <?= (int) $product['quantity_sold'] ?>
                                </td>

                                <td class="reports-table-money">
                                    <?= $money($product['total_revenue']) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <!-- EXPENSES -->

    <section class="reports-section">

        <div class="reports-section-heading">

            <div>

                <span class="reports-eyebrow">CONTROLE FINANCEIRO</span>

                <h2>Despesas</h2>

            </div>

            <a
                href="<?= $escape($exportUrl('expenses')) ?>"
                class="reports-button secondary"
            >
                Exportar CSV
            </a>

        </div>

        <div class="reports-expense-layout">

            <form
                method="POST"
                action="/platypus_gestoes/router.php?pagina=relatorios"
                class="reports-expense-form"
            >

                <h3>Registrar despesa</h3>

                <input
                    type="hidden"
                    name="action"
                    value="register-expense"
                >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= $escape($_SESSION['csrf_token'] ?? '') ?>"
                >

                <div class="reports-form-field">

                    <label for="expense-description">Descrição</label>

                    <input
                        type="text"
                        id="expense-description"
                        name="description"
                        maxlength="255"
                        placeholder="Ex.: Compra de ingredientes"
                        required
                    >

                </div>

                <div class="reports-form-field">

                    <label for="expense-category">Categoria</label>

                    <select
                        id="expense-category"
                        name="category"
                        required
                    >

                        <option value="">Selecione</option>
                        <option value="Ingredientes">Ingredientes</option>
                        <option value="Bebidas">Bebidas</option>
                        <option value="Energia">Energia</option>
                        <option value="Água">Água</option>
                        <option value="Manutenção">Manutenção</option>
                        <option value="Equipamentos">Equipamentos</option>
                        <option value="Limpeza">Limpeza</option>
                        <option value="Outros">Outros</option>

                    </select>

                </div>

                <div class="reports-form-row">

                    <div class="reports-form-field">

                        <label for="expense-amount">Valor (R$)</label>

                        <input
                            type="number"
                            id="expense-amount"
                            name="amount"
                            min="0.01"
                            step="0.01"
                            placeholder="0,00"
                            required
                        >

                    </div>

                    <div class="reports-form-field">

                        <label for="expense-date">Data</label>

                        <input
                            type="date"
                            id="expense-date"
                            name="expense_date"
                            value="<?= date('Y-m-d') ?>"
                            required
                        >

                    </div>

                </div>

                <button type="submit" class="reports-button primary full">
                    Registrar despesa
                </button>

            </form>

            <div class="reports-expense-history">

                <h3>Despesas registradas</h3>

                <div class="reports-table-wrapper">

                    <table class="reports-table">

                        <thead>

                            <tr>
                                <th>Descrição</th>
                                <th>Categoria</th>
                                <th>Data</th>
                                <th>Valor</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if (empty($expenses)): ?>

                                <tr>

                                    <td colspan="4" class="reports-empty">
                                        Nenhuma despesa registrada no período.
                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($expenses as $expense): ?>

                                    <tr>

                                        <td>
                                            <?= $escape($expense['description']) ?>
                                        </td>

                                        <td>
                                            <?= $escape($expense['category']) ?>
                                        </td>

                                        <td>
                                            <?= $escape(
                                                date(
                                                    'd/m/Y',
                                                    strtotime($expense['expense_date'])
                                                )
                                            ) ?>
                                        </td>

                                        <td class="reports-table-money">
                                            <?= $money($expense['amount']) ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </section>

    <!-- STOCK -->

    <section class="reports-section">

        <div class="reports-section-heading">

            <div>

                <span class="reports-eyebrow">INVENTÁRIO</span>

                <h2>Movimentações de estoque</h2>

            </div>

            <a
                href="<?= $escape($exportUrl('stock')) ?>"
                class="reports-button secondary"
            >
                Exportar CSV
            </a>

        </div>

        <div class="reports-table-wrapper">

            <table class="reports-table">

                <thead>

                    <tr>
                        <th>Item</th>
                        <th>Tipo</th>
                        <th>Movimentação</th>
                        <th>Quantidade</th>
                        <th>Motivo</th>
                        <th>Data</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (empty($stockMovements)): ?>

                        <tr>

                            <td colspan="6" class="reports-empty">
                                Nenhuma movimentação encontrada.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($stockMovements as $movement): ?>

                            <tr>

                                <td>
                                    <?= $escape(
                                        $movement['item_name'] ?? 'Item removido'
                                    ) ?>
                                </td>

                                <td>
                                    <?= $escape($movement['item_type']) ?>
                                </td>

                                <td>
                                    <?= $escape(
                                        $movementLabels[$movement['type']]
                                        ?? $movement['type']
                                    ) ?>
                                </td>

                                <td>
                                    <?= $escape($movement['quantity']) ?>
                                </td>

                                <td>
                                    <?= $escape($movement['reason'] ?? '—') ?>
                                </td>

                                <td>
                                    <?= $escape(
                                        date(
                                            'd/m/Y H:i',
                                            strtotime($movement['created_at'])
                                        )
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <p class="reports-disclaimer">
            As movimentações de estoque mostram quantidades e motivos
            registrados. Elas não representam, por si só, o custo financeiro
            dos produtos ou ingredientes.
        </p>

    </section>

</main>

<?php require __DIR__ . '/../../includes/footer.php'; ?>

</body>

</html>
