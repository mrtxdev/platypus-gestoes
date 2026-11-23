<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';


function normalizeRemovedIngredientIds(mixed $values): array
{
    if ($values === null || $values === '') {
        return [];
    }

    if (!is_array($values)) {
        throw new InvalidArgumentException('Personalização de ingredientes inválida.');
    }

    $ids = [];

    foreach ($values as $value) {
        $id = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);

        if ($id === false) {
            throw new InvalidArgumentException('Ingrediente selecionado inválido.');
        }

        $ids[] = (int) $id;
    }

    $ids = array_values(array_unique($ids));
    sort($ids, SORT_NUMERIC);

    return $ids;
}

function encodeRemovedIngredientIds(array $ids): string
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    sort($ids, SORT_NUMERIC);

    return json_encode($ids, JSON_THROW_ON_ERROR);
}

function decodeRemovedIngredientIds(?string $json): array
{
    if ($json === null || trim($json) === '') {
        return [];
    }

    $decoded = json_decode($json, true);

    if (!is_array($decoded)) {
        return [];
    }

    return normalizeRemovedIngredientIds($decoded);
}

/**
 * Confere no servidor se todos os ingredientes removidos pertencem à receita
 * e estão marcados como opcionais. Ingredientes obrigatórios nunca são aceitos.
 */
function validateRemovedIngredients(
    PDO $pdo,
    int $productId,
    array $removedIngredientIds
): array {
    $removedIngredientIds = normalizeRemovedIngredientIds($removedIngredientIds);

    if ($removedIngredientIds === []) {
        return [];
    }

    $stmt = $pdo->prepare(
        'SELECT ingredient_id, is_optional
         FROM recipes
         WHERE product_id = :product_id'
    );
    $stmt->execute(['product_id' => $productId]);

    $optionalIds = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $recipe) {
        if ((int) $recipe['is_optional'] === 1) {
            $optionalIds[] = (int) $recipe['ingredient_id'];
        }
    }

    foreach ($removedIngredientIds as $ingredientId) {
        if (!in_array($ingredientId, $optionalIds, true)) {
            throw new InvalidArgumentException(
                'Um ingrediente selecionado não pode ser removido deste produto.'
            );
        }
    }

    return $removedIngredientIds;
}


function buildRemovedIngredientNotes(PDO $pdo, array $removedIngredientIds): ?string
{
    $removedIngredientIds = normalizeRemovedIngredientIds($removedIngredientIds);
    if ($removedIngredientIds === []) {
        return null;
    }

    $placeholders = implode(',', array_fill(0, count($removedIngredientIds), '?'));
    $stmt = $pdo->prepare(
        "SELECT name FROM ingredients WHERE id IN ($placeholders) ORDER BY name ASC"
    );
    $stmt->execute($removedIngredientIds);
    $names = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($names === []) {
        return null;
    }

    return mb_substr('Sem: ' . implode(', ', $names), 0, 250);
}


function deductProductStock(
    PDO $pdo,
    int $productId,
    int $productQuantity,
    array $removedIngredientIds,
    int $orderId,
    string $productName
): void {
    if ($productId <= 0 || $productQuantity <= 0) {
        throw new InvalidArgumentException('Dados de estoque inválidos.');
    }

    $stmtProduct = $pdo->prepare(
        'SELECT id, stock_quantity
         FROM products
         WHERE id = :product_id
         FOR UPDATE'
    );
    $stmtProduct->execute(['product_id' => $productId]);
    $product = $stmtProduct->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new RuntimeException('Produto não encontrado durante a baixa do estoque.');
    }

    // Estoque individual: bebidas, sobremesas embaladas e produtos por unidade.
    if ($product['stock_quantity'] !== null) {
        $stmtUpdate = $pdo->prepare(
            'UPDATE products
             SET stock_quantity = stock_quantity - :quantity
             WHERE id = :product_id
               AND stock_quantity >= :quantity_check'
        );
        $stmtUpdate->execute([
            'quantity' => $productQuantity,
            'product_id' => $productId,
            'quantity_check' => $productQuantity
        ]);

        if ($stmtUpdate->rowCount() !== 1) {
            throw new RuntimeException(
                'Estoque insuficiente para o produto: ' . $productName . '.'
            );
        }

        $stmtMovement = $pdo->prepare(
            "INSERT INTO product_stock_movements
                (product_id, user_id, type, quantity, reason)
             VALUES
                (:product_id, NULL, 'OUT', :quantity, :reason)"
        );
        $stmtMovement->execute([
            'product_id' => $productId,
            'quantity' => $productQuantity,
            'reason' => 'Pedido #' . $orderId . ' - ' . $productName
        ]);

        return;
    }

    // Estoque por receita: cada ingrediente é descontado conforme a quantidade
    // usada na receita multiplicada pela quantidade de pratos pedidos.
    $stmtRecipe = $pdo->prepare(
        'SELECT
            r.ingredient_id,
            r.quantity,
            r.is_optional,
            i.name AS ingredient_name,
            i.unit,
            i.current_quantity,
            i.is_active
         FROM recipes r
         INNER JOIN ingredients i ON i.id = r.ingredient_id
         WHERE r.product_id = :product_id
         ORDER BY r.ingredient_id ASC
         FOR UPDATE'
    );
    $stmtRecipe->execute(['product_id' => $productId]);
    $recipeRows = $stmtRecipe->fetchAll(PDO::FETCH_ASSOC);

    if ($recipeRows === []) {
        // Produto sem stock_quantity e sem receita: estoque não controlado.
        if ($removedIngredientIds !== []) {
            throw new InvalidArgumentException('Este produto não possui ingredientes personalizáveis.');
        }
        return;
    }

    $removedIngredientIds = validateRemovedIngredients(
        $pdo,
        $productId,
        $removedIngredientIds
    );
    $removedLookup = array_fill_keys($removedIngredientIds, true);

    foreach ($recipeRows as $recipe) {
        $ingredientId = (int) $recipe['ingredient_id'];
        $isOptional = (int) $recipe['is_optional'] === 1;

        if ($isOptional && isset($removedLookup[$ingredientId])) {
            continue;
        }

        if (!(bool) $recipe['is_active']) {
            throw new RuntimeException(
                'O ingrediente ' . $recipe['ingredient_name'] . ' está inativo.'
            );
        }

        $requiredQuantity = (float) $recipe['quantity'] * $productQuantity;
        $requiredQuantitySql = number_format($requiredQuantity, 3, '.', '');

        $stmtUpdate = $pdo->prepare(
            'UPDATE ingredients
             SET current_quantity = current_quantity - :quantity
             WHERE id = :ingredient_id
               AND is_active = 1
               AND current_quantity >= :quantity_check'
        );
        $stmtUpdate->execute([
            'quantity' => $requiredQuantitySql,
            'ingredient_id' => $ingredientId,
            'quantity_check' => $requiredQuantitySql
        ]);

        if ($stmtUpdate->rowCount() !== 1) {
            throw new RuntimeException(
                'Estoque insuficiente do ingrediente: ' . $recipe['ingredient_name'] . '.'
            );
        }

        $stmtMovement = $pdo->prepare(
            "INSERT INTO inventory_movements
                (ingredient_id, user_id, type, quantity, reason)
             VALUES
                (:ingredient_id, NULL, 'OUT', :quantity, :reason)"
        );
        $stmtMovement->execute([
            'ingredient_id' => $ingredientId,
            'quantity' => $requiredQuantitySql,
            'reason' => 'Pedido #' . $orderId . ' - ' . $productName
                . ' (' . $productQuantity . ' unidade(s))'
        ]);
    }
}
