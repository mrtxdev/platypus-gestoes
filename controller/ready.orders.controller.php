
<?php

require_once __DIR__ . '/../config/database.php';

function getReadyOrders(): array
{
    try {
        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare("
            SELECT
                orders.id,
                orders.total_amount,
                orders.status,
                dining_tables.table_number
            FROM orders
            INNER JOIN dining_tables
                ON dining_tables.id = orders.table_id
            WHERE orders.status = 'READY'
            ORDER BY orders.created_at ASC
        ");

        $stmt->execute();

        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($orders)) {
            return [];
        }

        $itemStmt = $pdo->prepare("
            SELECT
                quantity,
                product_name
            FROM order_items
            WHERE order_id = :order_id
            ORDER BY id ASC
        ");

        foreach ($orders as &$order) {
            $itemStmt->execute([
                ':order_id' => $order['id']
            ]);

            $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        unset($order);

        return $orders;

    } catch (Throwable $e) {
        error_log('Ready orders error: ' . $e->getMessage());

        return [];
    }
}

/*
|--------------------------------------------------------------------------
| DELIVER READY ORDER
|--------------------------------------------------------------------------
*/

function deliverReadyOrder(): array
{
    $pdo = null;

    try {
        $pdo = getDatabaseConnection();

        $order_id = filter_var(
            $_POST['order_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $order_id === false
            || $order_id === null
            || $order_id <= 0
        ) {
            return [
                'success' => false,
                'message' => 'Invalid order.'
            ];
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT orders.status
            FROM orders
            INNER JOIN dining_tables
                ON dining_tables.id = orders.table_id
            WHERE orders.id = :order_id
            LIMIT 1
            FOR UPDATE
        ");

        $stmt->execute([
            ':order_id' => $order_id
        ]);

        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order || $order['status'] !== 'READY') {
            throw new Exception('Order is not ready for delivery.');
        }

        $stmt = $pdo->prepare("
            UPDATE orders
            SET status = 'DELIVERED',
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :order_id
        ");

        $stmt->execute([
            ':order_id' => $order_id
        ]);

        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Order delivered successfully.'
        ];

    } catch (Throwable $e) {

        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Deliver order error: ' . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Unable to deliver the order.'
        ];
    }
}
