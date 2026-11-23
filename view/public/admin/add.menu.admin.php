<?php
require_once __DIR__ . '/../../../controller/category.controller.php';
require_once __DIR__ . '/../../../controller/ingredient.controller.php';

$categories = all();
$ingredients = getAllIngredients(true);
$csrfToken = $_SESSION['csrf_token'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Platypus - Adicionar Produto</title>
    <link rel="stylesheet" href="/platypus_gestoes/view/public/assets/css/style.css?v=12">
    <link rel="shortcut icon" href="/platypus_gestoes/view/public/assets/images/icons/favicon.ico" type="image/x-icon">
</head>
<body>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<main>
    <div class="content-header">
        <h2>Adicionar Produto</h2>
        <p>Cadastre o produto, escolha como o estoque será controlado e monte sua receita.</p>
    </div>

    <?php if (!empty($_GET['erro'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars((string) $_GET['erro'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <section class="card">
        <form action="/platypus_gestoes/router.php?pagina=cardapio/admin/adicionar&action=register-product"
              method="POST" enctype="multipart/form-data" accept-charset="UTF-8">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="image">Imagem do produto *</label>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" required>
                <small>JPEG, PNG ou WebP. Máximo de 5 MB.</small>
            </div>

            <div class="form-group">
                <label for="name">Nome do produto *</label>
                <input type="text" id="name" name="name" maxlength="150" placeholder="Ex.: X-Burger da Casa" required>
            </div>

            <div class="form-group">
                <label for="category_id">Categoria</label>
                <select id="category_id" name="category_id">
                    <option value="">Selecione uma categoria</option>
                    <?php foreach ($categories as $category): ?>
                        <?php if ($category->isActive): ?>
                            <option value="<?= (int) $category->id ?>"><?= htmlspecialchars($category->name, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Descrição</label>
                <textarea id="description" name="description" maxlength="500" rows="4"
                          placeholder="Descreva o produto."></textarea>
            </div>

            <div class="form-group">
                <label for="price">Preço de venda (R$) *</label>
                <input type="number" id="price" name="price" min="0.01" max="99999999.99" step="0.01" placeholder="24.90" required>
            </div>

            <div class="form-group">
                <label for="is_available">Disponibilidade</label>
                <select id="is_available" name="is_available" required>
                    <option value="1" selected>Disponível</option>
                    <option value="0">Indisponível</option>
                </select>
            </div>

            <hr>
            <h3>Controle de estoque</h3>
            <div class="form-group">
                <label for="stock_mode">Como deseja controlar o estoque?</label>
                <select id="stock_mode" name="stock_mode" required>
                    <option value="NONE">Sem controle de estoque</option>
                    <option value="PRODUCT">Estoque individual do produto</option>
                    <option value="INGREDIENTS">Estoque pelos ingredientes da receita</option>
                </select>
                <small>Exemplo: bebidas usam estoque individual; lanches e pratos usam os ingredientes da receita.</small>
            </div>

            <div class="form-group" id="product-stock-field" hidden>
                <label for="stock_quantity">Quantidade disponível</label>
                <input type="number" id="stock_quantity" name="stock_quantity" min="0" max="2147483647" step="1" value="0">
            </div>

            <section id="recipe-section" hidden>
                <h3>Composição do prato</h3>
                <p>Ingredientes obrigatórios não podem ser retirados. Ingredientes opcionais aparecem ao cliente com a opção de remover.</p>
                <?php if ($ingredients === []): ?>
                    <div class="alert alert-error">
                        Nenhum ingrediente cadastrado. <a href="/platypus_gestoes/router.php?pagina=ingredientes">Cadastre os ingredientes primeiro.</a>
                    </div>
                <?php endif; ?>
                <div id="recipe-rows" class="form-group"></div>
                <button type="button" class="btn btn-secondary" id="add-recipe-row">+ Adicionar ingrediente</button>
            </section>

            <div class="form-actions form-actions-spaced">
                <button type="submit" class="btn">Cadastrar Produto</button>
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
                    <option value="<?= (int) $ingredient['id'] ?>">
                        <?= htmlspecialchars($ingredient['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($ingredient['unit'], ENT_QUOTES, 'UTF-8') ?>)
                    </option>
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
    let nextIndex = 0;

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

    function addRow() {
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
        rows.insertAdjacentHTML('beforeend', html);
    }

    function addRowIfAvailable() {
        if (<?= $ingredients === [] ? 'false' : 'true' ?>) addRow();
    }

    document.getElementById('add-recipe-row').addEventListener('click', addRow);
    mode.addEventListener('change', updateMode);

    rows.addEventListener('change', event => {
        if (event.target.matches('[data-optional-toggle]')) {
            const row = event.target.closest('.recipe-row');
            row.querySelector('[data-optional-value]').value = event.target.checked ? '1' : '0';
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
