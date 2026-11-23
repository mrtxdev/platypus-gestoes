<?php
require_once __DIR__ . '/../../../controller/product.controller.php';

$products = getAllProducts();
$h = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Platypus - Cardápio</title>
    <link rel="stylesheet" href="/platypus_gestoes/view/public/assets/css/style.css?v=12">
    <link rel="shortcut icon" href="/platypus_gestoes/view/public/assets/images/icons/favicon.ico" type="image/x-icon">
</head>
<body>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<main>
    <div class="content-header">
        <h3>Cardápio do Dia</h3>
        <p>Confira e gerencie os produtos cadastrados no restaurante.</p>
    </div>

    <?php if (!empty($_GET['erro'])): ?>
        <div class="alert alert-error"><?= $h($_GET['erro']) ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['sucesso'])): ?>
        <div class="alert alert-success"><?= $h($_GET['sucesso']) ?></div>
    <?php endif; ?>

    <div class="menu-top-nav">
        <a href="/platypus_gestoes/router.php?pagina=cardapio/admin/adicionar" class="btn">+ Adicionar Produto</a>
        <a href="/platypus_gestoes/router.php?pagina=categorias/adicionar" class="btn btn-secondary">+ Criar Categoria</a>
        <a href="/platypus_gestoes/router.php?pagina=ingredientes" class="btn btn-secondary">Gerenciar Ingredientes</a>
    </div>

    <div class="content-header"><p>Total de produtos: <strong><?= count($products) ?></strong></p></div>

    <?php if ($products === []): ?>
        <section class="card">
            <h4>Nenhum produto cadastrado</h4>
            <p>Cadastre seu primeiro produto para exibi-lo aqui.</p>
            <a href="/platypus_gestoes/router.php?pagina=cardapio/admin/adicionar" class="btn">Cadastrar Produto</a>
        </section>
    <?php else: ?>
        <ul class="menu-grid">
            <?php foreach ($products as $product): ?>
                <?php
                $isImageValid = !empty($product->image)
                    && basename($product->image) === $product->image
                    && is_file(__DIR__ . '/../assets/images/products/' . $product->image);
                $imageUrl = '/platypus_gestoes/view/public/assets/images/products/' . rawurlencode($product->image ?? '');
                ?>
                <li class="card menu-item">
                    <?php if ($isImageValid): ?>
                        <img class="menu-item-image" src="<?= $h($imageUrl) ?>" alt="<?= $h($product->name) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="menu-item-image" role="img" aria-label="Imagem não cadastrada">Sem imagem</div>
                    <?php endif; ?>

                    <div class="menu-item-body">
                        <h4 class="menu-item-title"><?= $h($product->name) ?></h4>
                        <p class="menu-item-description"><?= $h($product->description ?? 'Sem descrição cadastrada.') ?></p>
                        <p><strong>Disponibilidade:</strong> <?= $product->isAvailable ? 'Disponível' : 'Indisponível' ?></p>
                        <p><strong>Controle de estoque:</strong>
                            <?php if ($product->stockMode === 'PRODUCT'): ?>
                                Individual (<?= (int) $product->stockQuantity ?> unidades)
                            <?php elseif ($product->stockMode === 'INGREDIENTS'): ?>
                                Por ingredientes da receita
                            <?php else: ?>
                                Sem controle
                            <?php endif; ?>
                        </p>
                        <div class="menu-item-footer">
                            <span class="menu-item-price">R$ <?= number_format((float) $product->price, 2, ',', '.') ?></span>
                        </div>
                        <div class="form-actions">
                            <a href="/platypus_gestoes/router.php?pagina=cardapio/admin/editar&id=<?= (int) $product->id ?>" class="btn">Editar</a>
                            <form action="/platypus_gestoes/router.php?pagina=cardapio/admin&action=delete-product" method="POST"
                                  onsubmit="return confirm('Deseja realmente excluir este produto?');">
                                <input type="hidden" name="id" value="<?= (int) $product->id ?>">
                                <input type="hidden" name="csrf_token" value="<?= $h($_SESSION['csrf_token'] ?? '') ?>">
                                <button type="submit" class="btn btn-secondary">Excluir</button>
                            </form>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
