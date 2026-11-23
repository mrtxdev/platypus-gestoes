
<?php
require_once __DIR__ . '/../../../controller/ingredient.controller.php';

$ingredients = getAllIngredients();
$csrfToken = $_SESSION['csrf_token'] ?? '';

$h = static fn($value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    'UTF-8'
);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Platypus - Ingredientes e Estoque</title>

    <link
        rel="stylesheet"
        href="/platypus_gestoes/view/public/assets/css/style.css?v=12"
    >

    <link
        rel="shortcut icon"
        href="/platypus_gestoes/view/public/assets/images/icons/favicon.ico"
        type="image/x-icon"
    >
</head>

<body>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<main>

    <div class="content-header">
        <h2>Ingredientes e Estoque</h2>

        <p>
            Cadastre os ingredientes usados nas receitas e acompanhe
            as quantidades disponíveis.
        </p>
    </div>

    <?php if (!empty($_GET['erro'])): ?>
        <div class="alert alert-error">
            <?= $h($_GET['erro']) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['sucesso'])): ?>
        <div class="alert alert-success">
            <?= $h($_GET['sucesso']) ?>
        </div>
    <?php endif; ?>

    <!-- CADASTRO DE INGREDIENTE -->

    <section class="card">

        <h3>Novo ingrediente</h3>

        <form
            action="/platypus_gestoes/router.php?pagina=ingredientes&action=register-ingredient"
            method="POST"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= $h($csrfToken) ?>"
            >

            <div class="form-group">
                <label for="name">Nome *</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="120"
                    placeholder="Ex.: Queijo mussarela"
                    required
                >
            </div>

            <div class="form-group">
                <label for="unit">Unidade de medida *</label>

                <select id="unit" name="unit" required>
                    <option value="un">Unidade (un)</option>
                    <option value="g">Gramas (g)</option>
                    <option value="kg">Quilogramas (kg)</option>
                    <option value="ml">Mililitros (ml)</option>
                    <option value="l">Litros (l)</option>
                    <option value="mg">Miligramas (mg)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="current_quantity">Estoque inicial</label>

                <input
                    type="number"
                    id="current_quantity"
                    name="current_quantity"
                    min="0"
                    step="0.001"
                    value="0"
                    required
                >
            </div>

            <div class="form-group">
                <label for="minimum_quantity">Estoque mínimo</label>

                <input
                    type="number"
                    id="minimum_quantity"
                    name="minimum_quantity"
                    min="0"
                    step="0.001"
                    value="0"
                    required
                >
            </div>

            <button type="submit" class="btn">
                Cadastrar ingrediente
            </button>

        </form>

    </section>

    <!-- LISTAGEM -->

    <div class="content-header ingredient-section-heading">

        <h3>Ingredientes cadastrados</h3>

        <p>
            Total:
            <strong><?= count($ingredients) ?></strong>
        </p>

    </div>

    <?php if ($ingredients === []): ?>

        <section class="card">
            <p>Nenhum ingrediente cadastrado ainda.</p>
        </section>

    <?php else: ?>

        <div class="ingredient-list">

            <?php foreach ($ingredients as $ingredient): ?>

                <?php
                $lowStock =
                    (float) $ingredient['current_quantity']
                    <= (float) $ingredient['minimum_quantity'];

                $recipeLinks = (int) $ingredient['recipe_links'];
                $stockMovements = (int) $ingredient['stock_movements'];

                $isActive = (int) $ingredient['is_active'] === 1;
                ?>

                <section class="card ingredient-card">

                    <!-- INFORMAÇÕES DO INGREDIENTE -->

                    <div class="ingredient-overview">

                        <div>

                            <h3>
                                <?= $h($ingredient['name']) ?>
                            </h3>

                            <p>

                                Estoque atual:

                                <strong>
                                    <?= number_format(
                                        (float) $ingredient['current_quantity'],
                                        3,
                                        ',',
                                        '.'
                                    ) ?>

                                    <?= $h($ingredient['unit']) ?>
                                </strong>

                                <span>
                                    Mínimo:

                                    <?= number_format(
                                        (float) $ingredient['minimum_quantity'],
                                        3,
                                        ',',
                                        '.'
                                    ) ?>
                                </span>

                            </p>

                            <p class="<?= $lowStock ? 'ingredient-low-stock' : '' ?>">

                                <?= $lowStock
                                    ? 'Atenção: estoque no limite ou abaixo do mínimo.'
                                    : 'Estoque dentro do limite.'
                                ?>

                            </p>

                            <p>
                                Receitas vinculadas:
                                <strong><?= $recipeLinks ?></strong>
                            </p>

                            <p>
                                Movimentações de estoque:
                                <strong><?= $stockMovements ?></strong>
                            </p>

                            <p>

                                Status:

                                <?php if ($isActive): ?>

                                    <strong class="ingredient-status-active">
                                        Ativo
                                    </strong>

                                <?php else: ?>

                                    <strong class="ingredient-status-inactive">
                                        Inativo
                                    </strong>

                                <?php endif; ?>

                            </p>

                        </div>

                    </div>

                    <!-- EDIÇÃO E ESTOQUE -->

                    <div class="ingredient-actions">

                        <!-- EDITAR INGREDIENTE -->

                        <form
                            action="/platypus_gestoes/router.php?pagina=ingredientes&action=update-ingredient"
                            method="POST"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= $h($csrfToken) ?>"
                            >

                            <input
                                type="hidden"
                                name="ingredient_id"
                                value="<?= (int) $ingredient['id'] ?>"
                            >

                            <div class="form-group">

                                <label>Nome</label>

                                <input
                                    type="text"
                                    name="name"
                                    maxlength="120"
                                    value="<?= $h($ingredient['name']) ?>"
                                    required
                                >

                            </div>

                            <div class="form-group">

                                <label>Unidade</label>

                                <select name="unit" required>

                                    <?php foreach ([
                                        'un' => 'Unidade',
                                        'g' => 'Gramas',
                                        'kg' => 'Quilogramas',
                                        'ml' => 'Mililitros',
                                        'l' => 'Litros',
                                        'mg' => 'Miligramas'
                                    ] as $unit => $label): ?>

                                        <option
                                            value="<?= $unit ?>"
                                            <?= $ingredient['unit'] === $unit
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >
                                            <?= $label ?> (<?= $unit ?>)
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <div class="form-group">

                                <label>Estoque mínimo</label>

                                <input
                                    type="number"
                                    name="minimum_quantity"
                                    min="0"
                                    step="0.001"
                                    value="<?= $h($ingredient['minimum_quantity']) ?>"
                                    required
                                >

                            </div>

                            <div class="form-group">

                                <label>Status</label>

                                <select name="is_active">

                                    <option
                                        value="1"
                                        <?= $isActive ? 'selected' : '' ?>
                                    >
                                        Ativo
                                    </option>

                                    <option
                                        value="0"
                                        <?= !$isActive ? 'selected' : '' ?>
                                    >
                                        Inativo
                                    </option>

                                </select>

                            </div>

                            <button
                                type="submit"
                                class="btn btn-secondary"
                            >
                                Salvar dados
                            </button>

                        </form>

                        <!-- AJUSTAR ESTOQUE -->

                        <form
                            action="/platypus_gestoes/router.php?pagina=ingredientes&action=adjust-ingredient-stock"
                            method="POST"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= $h($csrfToken) ?>"
                            >

                            <input
                                type="hidden"
                                name="ingredient_id"
                                value="<?= (int) $ingredient['id'] ?>"
                            >

                            <div class="form-group">

                                <label>
                                    Definir estoque atual
                                    (<?= $h($ingredient['unit']) ?>)
                                </label>

                                <input
                                    type="number"
                                    name="current_quantity"
                                    min="0"
                                    step="0.001"
                                    value="<?= $h($ingredient['current_quantity']) ?>"
                                    required
                                >

                                <small>
                                    O sistema registra automaticamente
                                    o ajuste no histórico.
                                </small>

                            </div>

                            <button type="submit" class="btn">
                                Atualizar estoque
                            </button>

                        </form>

                    </div>

                    <!-- EXCLUSÃO DEFINITIVA -->

                    <form
                        action="/platypus_gestoes/router.php?pagina=ingredientes&action=delete-ingredient"
                        method="POST"
                        class="ingredient-delete-form"
                        onsubmit="return confirm('ATENÇÃO: deseja excluir permanentemente este ingrediente? Todas as receitas vinculadas e movimentações de estoque relacionadas também serão excluídas. Esta ação não poderá ser desfeita.');"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= $h($csrfToken) ?>"
                        >

                        <input
                            type="hidden"
                            name="ingredient_id"
                            value="<?= (int) $ingredient['id'] ?>"
                        >

                        <button
                            type="submit"
                            class="btn btn-secondary"
                        >
                            Excluir ingrediente
                        </button>

                    </form>

                </section>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <div class="form-actions ingredient-form-actions">

        <a
            href="/platypus_gestoes/router.php?pagina=cardapio/admin"
            class="btn btn-secondary"
        >
            Voltar ao cardápio
        </a>

    </div>

</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

</body>
</html>
