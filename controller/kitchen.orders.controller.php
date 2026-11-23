
<?php

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| GET KITCHEN ORDERS GROUPED BY TABLE
|--------------------------------------------------------------------------
*/

function getKitchenOrders(): array
{
    try {
        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare("
            SELECT
                orders.id AS order_id,
                orders.table_id,
                orders.status,
                orders.total_amount,
                orders.created_at,
                dining_tables.table_number,

                order_items.id AS item_id,
                order_items.quantity,
                order_items.product_name,
                order_items.unit_price

            FROM orders

            INNER JOIN dining_tables
                ON dining_tables.id = orders.table_id

            LEFT JOIN order_items
                ON order_items.order_id = orders.id

            WHERE orders.status IN ('PENDING', 'PREPARING')

            ORDER BY
                orders.created_at ASC,
                orders.id ASC,
                order_items.id ASC
        ");

        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return [];
        }

        $groups = [];

        foreach ($rows as $row) {

            $tableId = (int) $row['table_id'];
            $orderId = (int) $row['order_id'];

            if (!isset($groups[$tableId])) {

                $groups[$tableId] = [
                    'table_id' => $tableId,
                    'table_number' => $row['table_number'],

                    'status' => 'PENDING',
                    'created_at' => $row['created_at'],

                    'total_amount' => 0,
                    'order_count' => 0,

                    'orders' => [],
                    'items' => []
                ];

            }

            /*
             * Add each order only once.
             */

            if (!isset($groups[$tableId]['orders'][$orderId])) {

                $groups[$tableId]['orders'][$orderId] = [
                    'id' => $orderId,
                    'status' => $row['status'],
                    'total_amount' => (float) $row['total_amount']
                ];

                $groups[$tableId]['total_amount'] +=
                    (float) $row['total_amount'];

                $groups[$tableId]['order_count']++;

            }

            /*
             * Add the order items to the table group.
             */

            if ($row['item_id'] !== null) {

                $groups[$tableId]['items'][] = [
                    'id' => (int) $row['item_id'],
                    'order_id' => $orderId,
                    'quantity' => (int) $row['quantity'],
                    'product_name' => $row['product_name'],
                    'unit_price' => (float) $row['unit_price']
                ];

            }

        }

        /*
         * Calculate the combined status of each table.
         */

        foreach ($groups as &$group) {

            $hasPending = false;
            $hasPreparing = false;

            foreach ($group['orders'] as $order) {

                if ($order['status'] === 'PENDING') {
                    $hasPending = true;
                }

                if ($order['status'] === 'PREPARING') {
                    $hasPreparing = true;
                }

            }

            if ($hasPending && $hasPreparing) {

                $group['status'] = 'MIXED';

            } elseif ($hasPending) {

                $group['status'] = 'PENDING';

            } else {

                $group['status'] = 'PREPARING';

            }

            /*
             * Convert order IDs into a regular indexed array.
             */

            $group['orders'] = array_values($group['orders']);

            /*
             * Keep monetary values rounded to two decimals.
             */

            $group['total_amount'] = round(
                $group['total_amount'],
                2
            );

        }

        unset($group);

        return array_values($groups);

    } catch (Throwable $e) {

        error_log(
            'Kitchen orders error: ' . $e->getMessage()
        );

        return [];

    }
}

/*
|--------------------------------------------------------------------------
| UPDATE ALL ACTIVE ORDERS FROM A TABLE
|--------------------------------------------------------------------------
*/

function kitchenUpdateOrderStatus(): array
{
    $pdo = null;

    try {

        $pdo = getDatabaseConnection();

        $tableId = filter_var(
            $_POST['table_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $newStatus = strtoupper(
            trim((string) ($_POST['status'] ?? ''))
        );

        if (
            $tableId === false
            || $tableId === null
            || $tableId <= 0
            || !in_array(
                $newStatus,
                ['PREPARING', 'READY'],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid table information.'
            );
        }

        $pdo->beginTransaction();

        /*
         * Check whether the table still exists.
         */

        $stmt = $pdo->prepare("
            SELECT id
            FROM dining_tables
            WHERE id = :table_id
            FOR UPDATE
        ");

        $stmt->execute([
            ':table_id' => $tableId
        ]);

        $table = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$table) {
            throw new RuntimeException(
                'Table not found.'
            );
        }

        /*
         * Lock all active orders from the selected table.
         */

        $stmt = $pdo->prepare("
            SELECT
                id,
                status

            FROM orders

            WHERE table_id = :table_id
            AND status IN ('PENDING', 'PREPARING')

            ORDER BY id ASC

            FOR UPDATE
        ");

        $stmt->execute([
            ':table_id' => $tableId
        ]);

        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($orders)) {
            throw new RuntimeException(
                'No active orders found for this table.'
            );
        }

        $hasPending = false;
        $hasPreparing = false;

        foreach ($orders as $order) {

            if ($order['status'] === 'PENDING') {
                $hasPending = true;
            }

            if ($order['status'] === 'PREPARING') {
                $hasPreparing = true;
            }

        }

        /*
         * Start preparation:
         * Move all pending orders from the table to PREPARING.
         */

        if ($newStatus === 'PREPARING') {

            if (!$hasPending) {
                throw new RuntimeException(
                    'There are no pending orders to start.'
                );
            }

            $stmt = $pdo->prepare("
                UPDATE orders

                SET
                    status = 'PREPARING',
                    updated_at = CURRENT_TIMESTAMP

                WHERE table_id = :table_id
                AND status = 'PENDING'
            ");

            $stmt->execute([
                ':table_id' => $tableId
            ]);

            if ($stmt->rowCount() <= 0) {
                throw new RuntimeException(
                    'No pending orders could be updated.'
                );
            }

            $message = 'Preparo iniciado para todos os pedidos da mesa!';

        }

        /*
         * Mark as ready:
         * All active orders must already be PREPARING.
         */

        if ($newStatus === 'READY') {

            if ($hasPending || !$hasPreparing) {
                throw new RuntimeException(
                    'All orders must be in preparation before being marked ready.'
                );
            }

            $stmt = $pdo->prepare("
                UPDATE orders

                SET
                    status = 'READY',
                    updated_at = CURRENT_TIMESTAMP

                WHERE table_id = :table_id
                AND status = 'PREPARING'
            ");

            $stmt->execute([
                ':table_id' => $tableId
            ]);

            if ($stmt->rowCount() <= 0) {
                throw new RuntimeException(
                    'No orders could be marked as ready.'
                );
            }

            $message = 'Todos os pedidos da mesa foram marcados como prontos!';

        }

        $pdo->commit();

        return [
            'success' => true,
            'message' => $message
        ];

    } catch (Throwable $e) {

        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Kitchen status update error: ' . $e->getMessage()
        );

        return [
            'success' => false,
            'message' => 'Não foi possível atualizar os pedidos da mesa.'
        ];

    }
}
