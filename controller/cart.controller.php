<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/inventory.controller.php';
require_once __DIR__ . '/table.controller.php';

function getTableCart(int $tableId): array
{
    if ($tableId <= 0) {
        return [];
    }

    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare(
        'SELECT
            ci.id,
            ci.cart_id AS cartId,
            ci.product_id AS productId,
            ci.quantity,
            ci.unit_price AS unitPrice,
            ci.removed_ingredients AS removedIngredientsJson,
            COALESCE(p.name, "Produto indisponível") AS productName
         FROM cart_items ci
         INNER JOIN carts c ON c.id = ci.cart_id
         LEFT JOIN products p ON p.id = ci.product_id
         WHERE c.table_id = :table_id
           AND c.status = "OPEN"
         ORDER BY ci.id ASC'
    );
    $stmt->execute(['table_id' => $tableId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        $removedIds = decodeRemovedIngredientIds($item['removedIngredientsJson'] ?? null);
        $item['removedIngredients'] = $removedIds;
        $item['removedIngredientNames'] = [];

        if ($removedIds !== []) {
            $placeholders = implode(',', array_fill(0, count($removedIds), '?'));
            $stmtNames = $pdo->prepare(
                "SELECT name FROM ingredients WHERE id IN ($placeholders) ORDER BY name ASC"
            );
            $stmtNames->execute($removedIds);
            $item['removedIngredientNames'] = $stmtNames->fetchAll(PDO::FETCH_COLUMN);
        }

        unset($item['removedIngredientsJson']);
    }
    unset($item);

    return $items;
}

function getOrCreateCart(int $tableId, ?PDO $pdo = null): object
{
    if ($tableId <= 0) {
        throw new InvalidArgumentException('Mesa inválida.');
    }

    $pdo = $pdo ?? getDatabaseConnection();
    $ownsTransaction = !$pdo->inTransaction();

    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }

    try {
        $stmtTable = $pdo->prepare(
            "SELECT id, status
             FROM dining_tables
             WHERE id = :table_id
               AND status <> 'INACTIVE'
             FOR UPDATE"
        );
        $stmtTable->execute(['table_id' => $tableId]);
        $table = $stmtTable->fetch(PDO::FETCH_ASSOC);
        if (!$table) {
            throw new RuntimeException('Mesa não encontrada ou inativa.');
        }

        $stmtCart = $pdo->prepare(
            "SELECT id, table_id, status
             FROM carts
             WHERE table_id = :table_id
               AND status = 'OPEN'
             ORDER BY id DESC
             LIMIT 1
             FOR UPDATE"
        );
        $stmtCart->execute(['table_id' => $tableId]);
        $cart = $stmtCart->fetch(PDO::FETCH_OBJ);

        if (!$cart) {
            $stmtInsert = $pdo->prepare(
                "INSERT INTO carts (table_id, status) VALUES (:table_id, 'OPEN')"
            );
            $stmtInsert->execute(['table_id' => $tableId]);
            $cart = (object) [
                'id' => (int) $pdo->lastInsertId(),
                'table_id' => $tableId,
                'status' => 'OPEN'
            ];
        }

        if ($ownsTransaction) {
            $pdo->commit();
        }

        return $cart;
    } catch (Throwable $exception) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function addToCart(
    int $tableId,
    int $productId,
    int $quantity,
    array $removedIngredientIds = []
): bool {
    if ($tableId <= 0 || $productId <= 0 || $quantity < 1 || $quantity > 99) {
        throw new InvalidArgumentException('Parâmetros do carrinho inválidos.');
    }

    $pdo = getDatabaseConnection();

    try {
        $pdo->beginTransaction();
        $cart = getOrCreateCart($tableId, $pdo);

        $stmtProduct = $pdo->prepare(
            'SELECT id, name, price
             FROM products
             WHERE id = :product_id
               AND is_available = 1
             FOR UPDATE'
        );
        $stmtProduct->execute(['product_id' => $productId]);
        $product = $stmtProduct->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new RuntimeException('Produto não encontrado ou indisponível.');
        }

        $removedIngredientIds = validateRemovedIngredients(
            $pdo,
            $productId,
            $removedIngredientIds
        );
        $removedJson = encodeRemovedIngredientIds($removedIngredientIds);

        $stmtExisting = $pdo->prepare(
            'SELECT id, quantity
             FROM cart_items
             WHERE cart_id = :cart_id
               AND product_id = :product_id
               AND COALESCE(removed_ingredients, "[]") = :removed_ingredients
             LIMIT 1
             FOR UPDATE'
        );
        $stmtExisting->execute([
            'cart_id' => (int) $cart->id,
            'product_id' => $productId,
            'removed_ingredients' => $removedJson
        ]);
        $existing = $stmtExisting->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $newQuantity = (int) $existing['quantity'] + $quantity;
            if ($newQuantity > 99) {
                throw new InvalidArgumentException('O limite é de 99 unidades por personalização.');
            }

            $stmtUpdate = $pdo->prepare(
                'UPDATE cart_items
                 SET quantity = :quantity, unit_price = :unit_price
                 WHERE id = :id'
            );
            $stmtUpdate->execute([
                'quantity' => $newQuantity,
                'unit_price' => $product['price'],
                'id' => (int) $existing['id']
            ]);
        } else {
            $stmtInsert = $pdo->prepare(
                'INSERT INTO cart_items
                    (cart_id, product_id, quantity, unit_price, removed_ingredients)
                 VALUES
                    (:cart_id, :product_id, :quantity, :unit_price, :removed_ingredients)'
            );
            $stmtInsert->execute([
                'cart_id' => (int) $cart->id,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $product['price'],
                'removed_ingredients' => $removedJson
            ]);
        }

        $pdo->commit();
        return true;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function updateCartItemQuantity(int $quantity, int $cartItemId, int $tableId): bool
{
    if ($quantity < 1 || $quantity > 99 || $cartItemId <= 0 || $tableId <= 0) {
        throw new InvalidArgumentException('Parâmetros do carrinho inválidos.');
    }

    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare(
        'UPDATE cart_items ci
         INNER JOIN carts c ON c.id = ci.cart_id
         SET ci.quantity = :quantity
         WHERE ci.id = :cart_item_id
           AND c.table_id = :table_id
           AND c.status = "OPEN"'
    );
    $stmt->execute([
        'quantity' => $quantity,
        'cart_item_id' => $cartItemId,
        'table_id' => $tableId
    ]);

    return $stmt->rowCount() > 0;
}

function removeFromCart(int $cartItemId, int $tableId): bool
{
    if ($cartItemId <= 0 || $tableId <= 0) {
        throw new InvalidArgumentException('Parâmetros do carrinho inválidos.');
    }

    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare(
        'DELETE ci
         FROM cart_items ci
         INNER JOIN carts c ON c.id = ci.cart_id
         WHERE ci.id = :cart_item_id
           AND c.table_id = :table_id
           AND c.status = "OPEN"'
    );
    $stmt->execute([
        'cart_item_id' => $cartItemId,
        'table_id' => $tableId
    ]);

    return $stmt->rowCount() > 0;
}

function checkoutCart(int $tableId): int
{
    if ($tableId <= 0) {
        throw new InvalidArgumentException('Mesa inválida.');
    }

    $pdo = getDatabaseConnection();

    try {
        $pdo->beginTransaction();

        $stmtTable = $pdo->prepare(
            "SELECT id, status
             FROM dining_tables
             WHERE id = :table_id
               AND status <> 'INACTIVE'
             FOR UPDATE"
        );
        $stmtTable->execute(['table_id' => $tableId]);
        $table = $stmtTable->fetch(PDO::FETCH_ASSOC);
        if (!$table) {
            throw new RuntimeException('Mesa não encontrada ou inativa.');
        }

        $stmtCart = $pdo->prepare(
            "SELECT id
             FROM carts
             WHERE table_id = :table_id
               AND status = 'OPEN'
             ORDER BY id DESC
             LIMIT 1
             FOR UPDATE"
        );
        $stmtCart->execute(['table_id' => $tableId]);
        $cartId = $stmtCart->fetchColumn();
        if (!$cartId) {
            throw new RuntimeException('Não existe um carrinho aberto para esta mesa.');
        }

        $stmtItems = $pdo->prepare(
            'SELECT
                ci.id,
                ci.product_id,
                ci.quantity,
                ci.unit_price,
                ci.removed_ingredients,
                p.name AS product_name,
                p.is_available
             FROM cart_items ci
             LEFT JOIN products p ON p.id = ci.product_id
             WHERE ci.cart_id = :cart_id
             ORDER BY ci.id ASC
             FOR UPDATE'
        );
        $stmtItems->execute(['cart_id' => (int) $cartId]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        if ($items === []) {
            throw new RuntimeException('O carrinho está vazio.');
        }

        $totalCents = 0;
        foreach ($items as $item) {
            if (
                $item['product_id'] === null
                || $item['product_name'] === null
                || !(bool) $item['is_available']
            ) {
                throw new RuntimeException('Um produto do carrinho está indisponível.');
            }

            $itemQuantity = (int) $item['quantity'];
            $unitPriceCents = (int) round((float) $item['unit_price'] * 100);
            if ($itemQuantity < 1 || $itemQuantity > 99 || $unitPriceCents < 0) {
                throw new RuntimeException('Um item do carrinho possui dados inválidos.');
            }

            $removedIds = decodeRemovedIngredientIds($item['removed_ingredients'] ?? null);
            validateRemovedIngredients($pdo, (int) $item['product_id'], $removedIds);
            $totalCents += $unitPriceCents * $itemQuantity;
        }

        $stmtOrder = $pdo->prepare(
            "INSERT INTO orders (table_id, user_id, status, total_amount, notes)
             VALUES (:table_id, NULL, 'PENDING', :total_amount, NULL)"
        );
        $stmtOrder->execute([
            'table_id' => $tableId,
            'total_amount' => number_format($totalCents / 100, 2, '.', '')
        ]);
        $orderId = (int) $pdo->lastInsertId();

        $stmtOrderItem = $pdo->prepare(
            'INSERT INTO order_items
                (order_id, product_id, product_name, quantity, unit_price, notes, removed_ingredients)
             VALUES
                (:order_id, :product_id, :product_name, :quantity, :unit_price, :notes, :removed_ingredients)'
        );

        foreach ($items as $item) {
            $removedIds = decodeRemovedIngredientIds($item['removed_ingredients'] ?? null);
            $removedJson = encodeRemovedIngredientIds($removedIds);

            $stmtOrderItem->execute([
                'order_id' => $orderId,
                'product_id' => (int) $item['product_id'],
                'product_name' => $item['product_name'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => $item['unit_price'],
                'notes' => buildRemovedIngredientNotes($pdo, $removedIds),
                'removed_ingredients' => $removedJson
            ]);

            deductProductStock(
                $pdo,
                (int) $item['product_id'],
                (int) $item['quantity'],
                $removedIds,
                $orderId,
                (string) $item['product_name']
            );
        }

        if ($table['status'] === 'AVAILABLE') {
            updateTableStatus($pdo, $tableId, 'OCCUPIED');
        }

        $stmtClose = $pdo->prepare(
            "UPDATE carts SET status = 'CLOSED' WHERE id = :cart_id AND status = 'OPEN'"
        );
        $stmtClose->execute(['cart_id' => (int) $cartId]);
        if ($stmtClose->rowCount() !== 1) {
            throw new RuntimeException('Não foi possível fechar o carrinho.');
        }

        $pdo->commit();
        return $orderId;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}
