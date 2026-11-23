<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/inventory.controller.php';

function getClientOrdersByTableId(PDO $database, int $tableId): array
{
    $statement = $database->prepare(
        "SELECT o.id, o.status, o.total_amount, o.updated_at
         FROM orders o
         INNER JOIN dining_tables dt ON dt.id = o.table_id
         WHERE o.table_id = :table_id
           AND dt.status = 'OCCUPIED'
           AND dt.session_started_at IS NOT NULL
           AND o.created_at >= dt.session_started_at
         ORDER BY o.id DESC
         LIMIT 10"
    );
    $statement->execute(['table_id' => $tableId]);
    $orders = $statement->fetchAll(PDO::FETCH_ASSOC);

    if ($orders === []) {
        return [];
    }

    $orderIds = array_map(static fn(array $order): int => (int) $order['id'], $orders);
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $statement = $database->prepare(
        "SELECT order_id, product_name, quantity, removed_ingredients
         FROM order_items
         WHERE order_id IN ($placeholders)
         ORDER BY id ASC"
    );
    $statement->execute($orderIds);
    $items = $statement->fetchAll(PDO::FETCH_ASSOC);

    $itemsByOrder = [];
    foreach ($items as $item) {
        $orderId = (int) $item['order_id'];
        $removedIds = decodeRemovedIngredientIds($item['removed_ingredients'] ?? null);
        $removedNames = [];

        if ($removedIds !== []) {
            $ingredientPlaceholders = implode(',', array_fill(0, count($removedIds), '?'));
            $nameStatement = $database->prepare(
                "SELECT name FROM ingredients WHERE id IN ($ingredientPlaceholders) ORDER BY name ASC"
            );
            $nameStatement->execute($removedIds);
            $removedNames = $nameStatement->fetchAll(PDO::FETCH_COLUMN);
        }

        $itemsByOrder[$orderId][] = [
            'product_name' => $item['product_name'],
            'quantity' => (int) $item['quantity'],
            'removed_ingredient_names' => $removedNames
        ];
    }

    foreach ($orders as &$order) {
        $orderId = (int) $order['id'];
        $order['id'] = $orderId;
        $order['total_amount'] = (float) $order['total_amount'];
        $order['items'] = $itemsByOrder[$orderId] ?? [];
    }
    unset($order);

    return $orders;
}

function getClientMenuData(): array
{
    $menuData = [
        'table' => null,
        'products' => [],
        'orders' => [],
        'error' => null,
        'cartCount' => 0
    ];

    $token = trim((string) ($_GET['mesa'] ?? ''));
    if (!preg_match('/\A[a-f0-9]{36}\z/i', $token)) {
        http_response_code(404);
        $menuData['error'] = 'invalid';
        return $menuData;
    }

    try {
        $database = getDatabaseConnection();
        $statement = $database->prepare(
            'SELECT id, table_number, status, qr_token
             FROM dining_tables
             WHERE qr_token = :token
             LIMIT 1'
        );
        $statement->execute(['token' => $token]);
        $table = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$table || $table['status'] === 'INACTIVE') {
            http_response_code(404);
            $menuData['error'] = 'invalid';
            return $menuData;
        }

        $statement = $database->query(
            "SELECT
                p.id,
                p.name,
                p.description,
                p.price,
                p.image,
                p.stock_quantity,
                CASE
                    WHEN p.stock_quantity IS NOT NULL THEN 'PRODUCT'
                    WHEN EXISTS (SELECT 1 FROM recipes r WHERE r.product_id = p.id) THEN 'INGREDIENTS'
                    ELSE 'NONE'
                END AS stock_mode
             FROM products p
             WHERE p.is_available = 1
               AND (p.stock_quantity IS NULL OR p.stock_quantity > 0)
               AND NOT EXISTS (
                    SELECT 1
                    FROM recipes inactive_recipe
                    INNER JOIN ingredients inactive_ingredient
                        ON inactive_ingredient.id = inactive_recipe.ingredient_id
                    WHERE inactive_recipe.product_id = p.id
                      AND inactive_ingredient.is_active = 0
               )
             ORDER BY p.name ASC"
        );
        $products = $statement->fetchAll(PDO::FETCH_ASSOC);

        $recipeStatement = $database->prepare(
            'SELECT
                r.ingredient_id,
                r.quantity,
                r.is_optional,
                i.name,
                i.unit,
                i.is_active
             FROM recipes r
             INNER JOIN ingredients i ON i.id = r.ingredient_id
             WHERE r.product_id = :product_id
             ORDER BY r.is_optional ASC, i.name ASC'
        );

        $imageDirectory = dirname(__DIR__) . '/view/public/assets/images/products';
        $imageBaseUrl = '/platypus_gestoes/view/public/assets/images/products/';

        foreach ($products as $index => $product) {
            $imageFile = (string) ($product['image'] ?? '');
            $isImageValid = $imageFile !== ''
                && basename($imageFile) === $imageFile
                && preg_match('/\A[a-f0-9]{32}\.(jpg|png|webp)\z/i', $imageFile) === 1
                && is_file($imageDirectory . '/' . $imageFile);

            $products[$index]['imageUrl'] = $isImageValid
                ? $imageBaseUrl . rawurlencode($imageFile)
                : null;

            $recipeStatement->execute(['product_id' => (int) $product['id']]);
            $recipeRows = $recipeStatement->fetchAll(PDO::FETCH_ASSOC);
            $products[$index]['ingredients'] = [];
            $products[$index]['requiredIngredients'] = [];
            $products[$index]['optionalIngredients'] = [];

            foreach ($recipeRows as $recipe) {
                $ingredient = [
                    'id' => (int) $recipe['ingredient_id'],
                    'name' => $recipe['name'],
                    'quantity' => (float) $recipe['quantity'],
                    'unit' => $recipe['unit'],
                    'isOptional' => (bool) $recipe['is_optional'],
                    'isActive' => (bool) $recipe['is_active']
                ];
                $products[$index]['ingredients'][] = $ingredient;

                if ($ingredient['isOptional']) {
                    $products[$index]['optionalIngredients'][] = $ingredient;
                } else {
                    $products[$index]['requiredIngredients'][] = $ingredient;
                }
            }
        }

        $cartStatement = $database->prepare(
            "SELECT COALESCE(SUM(ci.quantity), 0)
             FROM cart_items ci
             INNER JOIN carts c ON c.id = ci.cart_id
             WHERE c.table_id = :table_id AND c.status = 'OPEN'"
        );
        $cartStatement->execute(['table_id' => (int) $table['id']]);

        $menuData['cartCount'] = (int) $cartStatement->fetchColumn();
        $menuData['orders'] = getClientOrdersByTableId($database, (int) $table['id']);
        $menuData['table'] = $table;
        $menuData['products'] = $products;
    } catch (Throwable $exception) {
        error_log('Client menu retrieval failed: ' . $exception->getMessage());
        http_response_code(500);
        $menuData['error'] = 'database';
    }

    return $menuData;
}
