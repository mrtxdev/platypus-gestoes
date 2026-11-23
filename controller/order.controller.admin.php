<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/table.controller.php';
require_once __DIR__ . '/inventory.controller.php';

function orderSave(): array
{
    $pdo = null;

    try {
        $pdo = getDatabaseConnection();
        $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);
        $tableToken = trim((string) ($_POST['table_token'] ?? ''));
        $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 99]
        ]);
        $removedIds = normalizeRemovedIngredientIds($_POST['removed_ingredients'] ?? []);

        if (
            $productId === false
            || $quantity === false
            || !preg_match('/\A[a-f0-9]{36}\z/i', $tableToken)
        ) {
            throw new InvalidArgumentException('Informações do pedido inválidas.');
        }

        $pdo->beginTransaction();

        $stmtTable = $pdo->prepare(
            'SELECT id, status
             FROM dining_tables
             WHERE qr_token = :table_token
             LIMIT 1
             FOR UPDATE'
        );
        $stmtTable->execute(['table_token' => $tableToken]);
        $table = $stmtTable->fetch(PDO::FETCH_ASSOC);

        if (!$table || $table['status'] === 'INACTIVE') {
            throw new RuntimeException('Mesa não encontrada ou inativa.');
        }

        $stmtProduct = $pdo->prepare(
            'SELECT id, name, price
             FROM products
             WHERE id = :product_id AND is_available = 1
             LIMIT 1
             FOR UPDATE'
        );
        $stmtProduct->execute(['product_id' => (int) $productId]);
        $product = $stmtProduct->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            throw new RuntimeException('Produto não encontrado ou indisponível.');
        }

        $removedIds = validateRemovedIngredients($pdo, (int) $productId, $removedIds);
        $total = number_format((float) $product['price'] * (int) $quantity, 2, '.', '');

        if ($table['status'] === 'AVAILABLE') {
            updateTableStatus($pdo, (int) $table['id'], 'OCCUPIED');
        }

        $stmtOrder = $pdo->prepare(
            "INSERT INTO orders (table_id, status, total_amount)
             VALUES (:table_id, 'PENDING', :total_amount)"
        );
        $stmtOrder->execute([
            'table_id' => (int) $table['id'],
            'total_amount' => $total
        ]);
        $orderId = (int) $pdo->lastInsertId();

        $stmtItem = $pdo->prepare(
            'INSERT INTO order_items
                (order_id, product_id, product_name, quantity, unit_price, notes, removed_ingredients)
             VALUES
                (:order_id, :product_id, :product_name, :quantity, :unit_price, :notes, :removed_ingredients)'
        );
        $stmtItem->execute([
            'order_id' => $orderId,
            'product_id' => (int) $product['id'],
            'product_name' => $product['name'],
            'quantity' => (int) $quantity,
            'unit_price' => $product['price'],
            'notes' => buildRemovedIngredientNotes($pdo, $removedIds),
            'removed_ingredients' => encodeRemovedIngredientIds($removedIds)
        ]);

        deductProductStock(
            $pdo,
            (int) $product['id'],
            (int) $quantity,
            $removedIds,
            $orderId,
            (string) $product['name']
        );

        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Pedido realizado com sucesso.',
            'order_id' => $orderId
        ];
    } catch (Throwable $exception) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Order error: ' . $exception->getMessage());
        return [
            'success' => false,
            'message' => $exception instanceof InvalidArgumentException || $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'Não foi possível realizar o pedido.'
        ];
    }
}

function updateKitchenOrderStatus(): array
{
    $pdo = null;

    try {
        $pdo = getDatabaseConnection();
        $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);
        $newStatus = strtoupper(trim((string) ($_POST['status'] ?? '')));

        if ($orderId === false || !in_array($newStatus, ['PREPARING', 'READY'], true)) {
            return ['success' => false, 'message' => 'Informações do pedido inválidas.'];
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT status FROM orders WHERE id = :id LIMIT 1 FOR UPDATE');
        $stmt->execute(['id' => (int) $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new RuntimeException('Pedido não encontrado.');
        }

        $currentStatus = $order['status'];
        $validTransition =
            ($currentStatus === 'PENDING' && $newStatus === 'PREPARING')
            || ($currentStatus === 'PREPARING' && $newStatus === 'READY');

        if (!$validTransition) {
            throw new RuntimeException('Mudança de status não permitida.');
        }

        $stmtUpdate = $pdo->prepare(
            'UPDATE orders
             SET status = :new_status, updated_at = CURRENT_TIMESTAMP
             WHERE id = :order_id'
        );
        $stmtUpdate->execute([
            'new_status' => $newStatus,
            'order_id' => (int) $orderId
        ]);

        $pdo->commit();
        return [
            'success' => true,
            'message' => $newStatus === 'READY'
                ? 'Pedido marcado como pronto.'
                : 'Preparação do pedido iniciada.'
        ];
    } catch (Throwable $exception) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Kitchen order update error: ' . $exception->getMessage());
        return ['success' => false, 'message' => 'Não foi possível atualizar o pedido.'];
    }
}
