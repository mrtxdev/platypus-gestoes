
<?php

$ingredients = $ingredients ?? [];
$products = $products ?? [];
$movements = $movements ?? [];

$csrfToken = $_SESSION['csrf_token'] ?? '';

$message = $_SESSION['quick_stock_message'] ?? '';
$success = $_SESSION['quick_stock_success'] ?? false;

unset(
    $_SESSION['quick_stock_message'],
    $_SESSION['quick_stock_success']
);

$escape = static function ($value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$getStockStatus = static function (
    float $quantity,
    float $minimum
): array {
    if ($quantity <= 0) {
        return [
            'label' => 'Esgotado',
            'class' => 'stock-status-empty'
        ];
    }

    if ($quantity <= $minimum) {
        return [
            'label' => 'Estoque baixo',
            'class' => 'stock-status-low'
        ];
    }

    return [
        'label' => 'Normal',
        'class' => 'stock-status-normal'
    ];
};

$movementLabels = [
    'IN' => 'Entrada',
    'OUT' => 'Saída',
    'ADJUSTMENT' => 'Ajuste'
];

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Platypus - Estoque Rápido</title>

    <link
        rel="stylesheet"
        href="/platypus_gestoes/view/public/assets/css/style.css?v=12"
    >

</head>

<body>

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="stock-quick-management">

        <section class="page-header">

            <h1>Estoque rápido</h1>

            <p>
                Acompanhe os níveis de estoque e registre movimentações
                de ingredientes e produtos.
            </p>

        </section>

        <?php if ($message !== ''): ?>

            <div class="<?= $success ? 'alert-success' : 'alert-error' ?>">

                <?= $escape($message) ?>

            </div>

        <?php endif; ?>


        <!-- RESUMO DO ESTOQUE -->

        <section class="stock-summary">

            <?php

            $totalIngredients = count($ingredients);
            $totalProducts = count($products);

            $lowStockCount = 0;
            $emptyStockCount = 0;

            foreach ($ingredients as $ingredient) {

                $quantity = (float) $ingredient['current_quantity'];
                $minimum = (float) $ingredient['minimum_quantity'];

                if ($quantity <= 0) {
                    $emptyStockCount++;
                } elseif ($quantity <= $minimum) {
                    $lowStockCount++;
                }

            }

            foreach ($products as $product) {

                $quantity = (float) $product['stock_quantity'];
                $minimum = (float) ($product['minimum_quantity'] ?? 0);

                if ($quantity <= 0) {
                    $emptyStockCount++;
                } elseif ($minimum > 0 && $quantity <= $minimum) {
                    $lowStockCount++;
                }

            }

            ?>

            <article class="stock-summary-card">

                <span class="stock-summary-label">
                    Ingredientes
                </span>

                <strong>
                    <?= $totalIngredients ?>
                </strong>

            </article>

            <article class="stock-summary-card">

                <span class="stock-summary-label">
                    Produtos
                </span>

                <strong>
                    <?= $totalProducts ?>
                </strong>

            </article>

            <article class="stock-summary-card stock-summary-warning">

                <span class="stock-summary-label">
                    Estoque baixo
                </span>

                <strong>
                    <?= $lowStockCount ?>
                </strong>

            </article>

            <article class="stock-summary-card stock-summary-danger">

                <span class="stock-summary-label">
                    Esgotados
                </span>

                <strong>
                    <?= $emptyStockCount ?>
                </strong>

            </article>

        </section>


        <!-- INGREDIENTES -->

        <section class="stock-section">

            <div class="stock-section-header">

                <div>

                    <h2>Ingredientes</h2>

                    <p>
                        Controle os ingredientes utilizados na preparação
                        dos produtos.
                    </p>

                </div>

            </div>

            <?php if (empty($ingredients)): ?>

                <div class="stock-empty-state">

                    <h3>Nenhum ingrediente encontrado</h3>

                    <p>
                        Os ingredientes cadastrados aparecerão nesta seção.
                    </p>

                </div>

            <?php else: ?>

                <div class="stock-grid">

                    <?php foreach ($ingredients as $ingredient): ?>

                        <?php

                        $quantity = (float) $ingredient['current_quantity'];
                        $minimum = (float) $ingredient['minimum_quantity'];

                        $status = $getStockStatus($quantity, $minimum);

                        ?>

                        <article class="stock-card">

                            <div class="stock-card-header">

                                <h3>
                                    <?= $escape($ingredient['name']) ?>
                                </h3>

                                <span class="stock-status <?= $escape($status['class']) ?>">

                                    <?= $escape($status['label']) ?>

                                </span>

                            </div>

                            <div class="stock-card-information">

                                <div>

                                    <span>Quantidade atual</span>

                                    <strong>
                                        <?= number_format($quantity, 3, ',', '.') ?>
                                        <?= $escape($ingredient['unit']) ?>
                                    </strong>

                                </div>

                                <div>

                                    <span>Quantidade mínima</span>

                                    <strong>
                                        <?= number_format($minimum, 3, ',', '.') ?>
                                        <?= $escape($ingredient['unit']) ?>
                                    </strong>

                                </div>

                            </div>

                            <div class="stock-card-actions">

                                <form
                                    method="POST"
                                    action="/platypus_gestoes/router.php?pagina=estoque-rapido"
                                    class="stock-action-form"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= $escape($csrfToken) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="register-loss"
                                    >

                                    <input
                                        type="hidden"
                                        name="stock_type"
                                        value="ingredient"
                                    >

                                    <input
                                        type="hidden"
                                        name="stock_id"
                                        value="<?= (int) $ingredient['id'] ?>"
                                    >

                                    <label>
                                        Registrar perda
                                    </label>

                                    <input
                                        type="number"
                                        name="quantity"
                                        min="0.001"
                                        step="0.001"
                                        max="<?= $quantity ?>"
                                        placeholder="Quantidade"
                                        required
                                    >

                                    <input
                                        type="text"
                                        name="reason"
                                        maxlength="255"
                                        placeholder="Motivo da perda"
                                        required
                                    >

                                    <button
                                        type="submit"
                                        class="stock-loss-button"
                                        <?= $quantity <= 0 ? 'disabled' : '' ?>
                                    >
                                        Registrar perda
                                    </button>

                                </form>


                                <form
                                    method="POST"
                                    action="/platypus_gestoes/router.php?pagina=estoque-rapido"
                                    class="stock-action-form"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= $escape($csrfToken) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="adjust-stock"
                                    >

                                    <input
                                        type="hidden"
                                        name="stock_type"
                                        value="ingredient"
                                    >

                                    <input
                                        type="hidden"
                                        name="stock_id"
                                        value="<?= (int) $ingredient['id'] ?>"
                                    >

                                    <label>
                                        Ajustar estoque
                                    </label>

                                    <input
                                        type="number"
                                        name="quantity"
                                        min="0"
                                        step="0.001"
                                        placeholder="Nova quantidade"
                                        required
                                    >

                                    <input
                                        type="text"
                                        name="reason"
                                        maxlength="255"
                                        placeholder="Motivo do ajuste"
                                        required
                                    >

                                    <button
                                        type="submit"
                                        class="stock-adjust-button"
                                    >
                                        Ajustar quantidade
                                    </button>

                                </form>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>


        <!-- PRODUTOS -->

        <section class="stock-section">

            <div class="stock-section-header">

                <div>

                    <h2>Produtos</h2>

                    <p>
                        Controle o estoque individual dos produtos
                        que possuem quantidade própria.
                    </p>

                </div>

            </div>

            <?php if (empty($products)): ?>

                <div class="stock-empty-state">

                    <h3>Nenhum produto com estoque próprio</h3>

                    <p>
                        Produtos com controle individual de estoque aparecerão aqui.
                    </p>

                </div>

            <?php else: ?>

                <div class="stock-grid">

                    <?php foreach ($products as $product): ?>

                        <?php

                        $quantity = (float) $product['stock_quantity'];

                        $minimum = (float) ($product['minimum_quantity'] ?? 0);

                        $status = $getStockStatus($quantity, $minimum);

                        ?>

                        <article class="stock-card">

                            <div class="stock-card-header">

                                <h3>
                                    <?= $escape($product['name']) ?>
                                </h3>

                                <span class="stock-status <?= $escape($status['class']) ?>">

                                    <?= $escape($status['label']) ?>

                                </span>

                            </div>

                            <div class="stock-card-information">

                                <div>

                                    <span>Quantidade atual</span>

                                    <strong>
                                        <?= number_format($quantity, 0, ',', '.') ?>
                                        unidades
                                    </strong>

                                </div>

                                <div>

                                    <span>Identificação</span>

                                    <strong>
                                        Produto #<?= (int) $product['id'] ?>
                                    </strong>

                                </div>

                            </div>

                            <div class="stock-card-actions">

                                <form
                                    method="POST"
                                    action="/platypus_gestoes/router.php?pagina=estoque-rapido"
                                    class="stock-action-form"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= $escape($csrfToken) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="register-loss"
                                    >

                                    <input
                                        type="hidden"
                                        name="stock_type"
                                        value="product"
                                    >

                                    <input
                                        type="hidden"
                                        name="stock_id"
                                        value="<?= (int) $product['id'] ?>"
                                    >

                                    <label>
                                        Registrar perda
                                    </label>

                                    <input
                                        type="number"
                                        name="quantity"
                                        min="1"
                                        step="1"
                                        max="<?= (int) $quantity ?>"
                                        placeholder="Quantidade"
                                        required
                                    >

                                    <input
                                        type="text"
                                        name="reason"
                                        maxlength="255"
                                        placeholder="Motivo da perda"
                                        required
                                    >

                                    <button
                                        type="submit"
                                        class="stock-loss-button"
                                        <?= $quantity <= 0 ? 'disabled' : '' ?>
                                    >
                                        Registrar perda
                                    </button>

                                </form>


                                <form
                                    method="POST"
                                    action="/platypus_gestoes/router.php?pagina=estoque-rapido"
                                    class="stock-action-form"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= $escape($csrfToken) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="adjust-stock"
                                    >

                                    <input
                                        type="hidden"
                                        name="stock_type"
                                        value="product"
                                    >

                                    <input
                                        type="hidden"
                                        name="stock_id"
                                        value="<?= (int) $product['id'] ?>"
                                    >

                                    <label>
                                        Ajustar estoque
                                    </label>

                                    <input
                                        type="number"
                                        name="quantity"
                                        min="0"
                                        step="1"
                                        placeholder="Nova quantidade"
                                        required
                                    >

                                    <input
                                        type="text"
                                        name="reason"
                                        maxlength="255"
                                        placeholder="Motivo do ajuste"
                                        required
                                    >

                                    <button
                                        type="submit"
                                        class="stock-adjust-button"
                                    >
                                        Ajustar quantidade
                                    </button>

                                </form>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>


        <!-- HISTÓRICO DE MOVIMENTAÇÕES -->

        <section class="stock-section stock-history-section">

            <div class="stock-section-header">

                <div>

                    <h2>Histórico de movimentações</h2>

                    <p>
                        Consulte as últimas alterações realizadas no estoque.
                    </p>

                </div>

            </div>

            <?php if (empty($movements)): ?>

                <div class="stock-empty-state">

                    <h3>Nenhuma movimentação registrada</h3>

                    <p>
                        As entradas, saídas e ajustes aparecerão aqui.
                    </p>

                </div>

            <?php else: ?>

                <div class="stock-history-wrapper">

                    <table class="stock-history-table">

                        <thead>

                            <tr>

                                <th>Item</th>
                                <th>Tipo</th>
                                <th>Movimentação</th>
                                <th>Quantidade</th>
                                <th>Responsável</th>
                                <th>Data</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($movements as $movement): ?>

                                <?php

                                $movementType = $movement['type'] ?? '';

                                $movementLabel = $movementLabels[$movementType]
                                    ?? 'Desconhecido';

                                $movementClass = strtolower($movementType);

                                ?>

                                <tr>

                                    <td>
                                        <?= $escape($movement['item_name'] ?? 'Item não identificado') ?>
                                    </td>

                                    <td>
                                        <?= $escape($movement['item_type'] ?? 'Não identificado') ?>
                                    </td>

                                    <td>

                                        <span class="movement-type movement-type-<?= $escape($movementClass) ?>">

                                            <?= $escape($movementLabel) ?>

                                        </span>

                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float) ($movement['quantity'] ?? 0),
                                            3,
                                            ',',
                                            '.'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $escape($movement['user_name'] ?? 'Sistema') ?>
                                    </td>

                                    <td>
                                        <?= !empty($movement['created_at'])
                                            ? $escape(date(
                                                'd/m/Y H:i',
                                                strtotime($movement['created_at'])
                                            ))
                                            : 'Não informado' ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>

    </main>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>

</body>

</html>
