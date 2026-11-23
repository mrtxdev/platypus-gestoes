<?php
require_once __DIR__ . '/../../../controller/product.controller.php';
require_once __DIR__ . '/../../../controller/category.controller.php';
require_once __DIR__ . '/../../../controller/ingredient.controller.php';

$productId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);
if ($productId === false) {
    header('Location: /platypus_gestoes/router.php?pagina=cardapio/admin&erro=' . urlencode('ID inválido.'));
    exit;
}

$product = getProductById((int) $productId);
if ($product === null) {
    header('Location: /platypus_gestoes/router.php?pagina=cardapio/admin&erro=' . urlencode('Produto não encontrado.'));
    exit;
}

$categories = all();
$ingredients = getAllIngredients(false);
$recipe = getProductRecipe((int) $product->id);
$csrfToken = $_SESSION['csrf_token'] ?? '';
$currentImage = !empty($product->image)
    && basename($product->image) === $product->image
    && is_file(__DIR__ . '/../assets/images/products/' . $product->image);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Platypus - Editar Produto</title>
    <link rel="stylesheet" href="/platypus_gestoes/view/public/assets/css/style.css?v=12">
    <link rel="shortcut icon" href="/platypus_gestoes/view/public/assets/images/icons/favicon.ico" type="image/x-icon">
</head>
<body>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<main>
    <div class="content-header">
        <h2>Editar Produto</h2>
        <p>Atualize os dados, o estoque e a composição do produto.</p>
    </div>
    <?php if (!empty($_GET['erro'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars((string) $_GET['erro'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <section class="card">
        <form action="/platypus_gestoes/router.php?pagina=cardapio/admin/editar&action=update-product"
              method="POST" enctype="multipart/form-data" accept-charset="UTF-8">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" value="<?= (int) $product->id ?>">

            <div class="form-group">
                <label>Imagem atual</label>
                <?php if ($currentImage): ?>
                    <img src="/platypus_gestoes/view/public/assets/images/products/<?= rawurlencode($product->image) ?>"
                         alt="<?= htmlspecialchars($product->name, ENT_QUOTES, 'UTF-8') ?>"
                         class="product-current-image">
                <?php else: ?>
                    <p>Este produto não possui uma imagem válida cadastrada.</p>
                <?php endif; ?>
                <label for="image">Escolher nova imagem</label>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                <small>JPEG, PNG ou WebP. Máximo de 5 MB. Deixe vazio para manter a imagem atual.</small>
            </div>

            <div class="form-group">
                <label for="name">Nome do produto *</label>
                <input type="text" id="name" name="name" maxlength="150" required
                       value="<?= htmlspecialchars($product->name, ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label for="category_id">Categoria</label>
                <select id="category_id" name="category_id">
                    <option value="">Sem categoria</option>
                    <?php foreach ($categories as $category): ?>
                        <?php if ($category->isActive || (int) $category->id === (int) $product->categoryId): ?>
                            <option value="<?= (int) $category->id ?>" <?= (int) $product->categoryId === (int) $category->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category->name, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Descrição</label>
                <textarea id="description" name="description" maxlength="500" rows="4"><?= htmlspecialchars($product->description ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="form-group">
                <label for="price">Preço de venda (R$) *</label>
                <input type="number" id="price" name="price" min="0.01" max="99999999.99" step="0.01" required
                       value="<?= htmlspecialchars((string) $product->price, ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label for="is_available">Disponibilidade</label>
                <select id="is_available" name="is_available" required>
                    <option value="1" <?= $product->isAvailable ? 'selected' : '' ?>>Disponível</option>
                    <option value="0" <?= !$product->isAvailable ? 'selected' : '' ?>>Indisponível</option>
                </select>
            </div>

            <hr>
            <h3>Controle de estoque</h3>
            <div class="form-group">
                <label for="stock_mode">Tipo de controle</label>
                <select id="stock_mode" name="stock_mode" required>
                    <option value="NONE" <?= $product->stockMode === 'NONE' ? 'selected' : '' ?>>Sem controle de estoque</option>
                    <option value="PRODUCT" <?= $product->stockMode === 'PRODUCT' ? 'selected' : '' ?>>Estoque individual do produto</option>
                    <option value="INGREDIENTS" <?= $product->stockMode === 'INGREDIENTS' ? 'selected' : '' ?>>Estoque pelos ingredientes da receita</option>
                </select>
            </div>

            <div class="form-group" id="product-stock-field" hidden>
                <label for="stock_quantity">Quantidade disponível</label>
                <input type="number" id="stock_quantity" name="stock_quantity" min="0" max="2147483647" step="1"
                       value="<?= $product->stockQuantity === null ? '0' : (int) $product->stockQuantity ?>">
            </div>

            <section id="recipe-section" hidden>
                <h3>Composição do prato</h3>
                <p>Marque como opcional somente o ingrediente que o cliente poderá retirar.</p>
                <?php if ($ingredients === []): ?>
                    <div class="alert alert-error">Nenhum ingrediente cadastrado. <a href="/platypus_gestoes/router.php?pagina=ingredientes">Cadastrar ingredientes</a>.</div>
                <?php endif; ?>
                <div id="recipe-rows">
                    <?php foreach ($recipe as $index => $item): ?>
                        <div class="card recipe-row">
                            <div class="form-group">
                                <label>Ingrediente</label>
                                <select name="recipe[ingredient_id][<?= (int) $index ?>]" required>
                                    <option value="">Selecione um ingrediente</option>
                                    <?php foreach ($ingredients as $ingredient): ?>
                                        <option value="<?= (int) $ingredient['id'] ?>" <?= (int) $item['ingredientId'] === (int) $ingredient['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($ingredient['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($ingredient['unit'], ENT_QUOTES, 'UTF-8') ?>)<?= (int) $ingredient['is_active'] === 1 ? '' : ' [inativo]' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Quantidade utilizada por prato</label>
                                <input type="number" name="recipe[quantity][<?= (int) $index ?>]" min="0.001" step="0.001" required
                                       value="<?= htmlspecialchars((string) $item['quantity'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="recipe[is_optional][<?= (int) $index ?>]" value="<?= (int) $item['isOptional'] ?>" data-optional-value>
                                <label><input type="checkbox" data-optional-toggle <?= (int) $item['isOptional'] === 1 ? 'checked' : '' ?>> Ingrediente opcional (cliente pode retirar)</label>
                            </div>
                            <button type="button" class="btn btn-secondary" data-remove-recipe>Remover ingrediente</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-secondary" id="add-recipe-row">+ Adicionar ingrediente</button>
            </section>

            <div class="form-actions form-actions-spaced">
                <button type="submit" class="btn">Salvar alterações</button>
                <a href="/platypus_gestoes/router.php?pagina=cardapio/admin" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<template id="recipe-row-template">
    <div class="card recipe-row">
        <div class="form-group">
            <label>Ingrediente</label>
            <select name="recipe[ingredient_id][__INDEX__]" required>
                <option value="">Selecione um ingrediente</option>
                <?php foreach ($ingredients as $ingredient): ?>
                    <?php if ((int) $ingredient['is_active'] === 1): ?>
                        <option value="<?= (int) $ingredient['id'] ?>"><?= htmlspecialchars($ingredient['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($ingredient['unit'], ENT_QUOTES, 'UTF-8') ?>)</option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Quantidade utilizada por prato</label>
            <input type="number" name="recipe[quantity][__INDEX__]" min="0.001" step="0.001" required>
        </div>
        <div class="form-group">
            <input type="hidden" name="recipe[is_optional][__INDEX__]" value="0" data-optional-value>
            <label><input type="checkbox" data-optional-toggle> Ingrediente opcional (cliente pode retirar)</label>
        </div>
        <button type="button" class="btn btn-secondary" data-remove-recipe>Remover ingrediente</button>
    </div>
</template>

<script>
(() => {
    const mode = document.getElementById('stock_mode');
    const stockField = document.getElementById('product-stock-field');
    const stockInput = document.getElementById('stock_quantity');
    const recipeSection = document.getElementById('recipe-section');
    const rows = document.getElementById('recipe-rows');
    const template = document.getElementById('recipe-row-template');
    let nextIndex = <?= count($recipe) ?>;

    function updateMode() {
        stockField.hidden = mode.value !== 'PRODUCT';
        recipeSection.hidden = mode.value !== 'INGREDIENTS';
        stockInput.required = mode.value === 'PRODUCT';
        rows.querySelectorAll('input, select, button').forEach(element => {
            element.disabled = mode.value !== 'INGREDIENTS';
        });
        if (mode.value === 'INGREDIENTS' && rows.querySelectorAll('.recipe-row').length === 0) {
            addRowIfAvailable();
        }
    }

    function addRowIfAvailable() {
        if (<?= $ingredients === [] ? 'false' : 'true' ?>) {
            rows.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(nextIndex++)));
            updateMode();
        }
    }

    document.getElementById('add-recipe-row').addEventListener('click', () => {
        rows.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(nextIndex++)));
    });
    mode.addEventListener('change', updateMode);

    rows.addEventListener('change', event => {
        if (event.target.matches('[data-optional-toggle]')) {
            event.target.closest('.recipe-row').querySelector('[data-optional-value]').value = event.target.checked ? '1' : '0';
        }
    });
    rows.addEventListener('click', event => {
        const button = event.target.closest('[data-remove-recipe]');
        if (button) button.closest('.recipe-row').remove();
    });

    updateMode();
})();
</script>
</body>
</html>
