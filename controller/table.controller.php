
<?php

require_once __DIR__ . '/../config/database.php';

function updateTableStatus(
    PDO $pdo,
    int $tableId,
    string $newStatus
): void {
    $newStatus = strtoupper(trim($newStatus));

    $validTransitions = [
        'OCCUPIED' => 'AVAILABLE',
        'AVAILABLE' => 'OCCUPIED'
    ];

    if (!isset($validTransitions[$newStatus])) {
        throw new InvalidArgumentException('Invalid table status.');
    }

    $currentStatus = $validTransitions[$newStatus];

    $stmt = $pdo->prepare("
        UPDATE dining_tables
        SET
            status = :new_status,
            session_started_at = CASE
                WHEN :session_status = 'OCCUPIED'
                    THEN CURRENT_TIMESTAMP
                ELSE NULL
            END
        WHERE id = :table_id
        AND status = :current_status
    ");

    $stmt->execute([
        ':new_status' => $newStatus,
        ':session_status' => $newStatus,
        ':table_id' => $tableId,
        ':current_status' => $currentStatus
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception('Unable to update table status.');
    }
}

function getDiningTables(): array
{
    $pdo = getDatabaseConnection();

    $stmt = $pdo->query("
        SELECT *
        FROM dining_tables
        ORDER BY table_number ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function releaseDiningTable(int $tableId): void
{
    $pdo = getDatabaseConnection();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT id, status
            FROM dining_tables
            WHERE id = :table_id
            FOR UPDATE
        ");

        $stmt->execute([
            ':table_id' => $tableId
        ]);

        $table = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$table) {
            throw new Exception('Table not found.');
        }

        if ($table['status'] !== 'OCCUPIED') {
            throw new Exception('Table is not occupied.');
        }

        $stmt = $pdo->prepare("
            DELETE ci
            FROM cart_items ci
            INNER JOIN carts c ON c.id = ci.cart_id
            WHERE c.table_id = :table_id
            AND c.status = 'OPEN'
        ");

        $stmt->execute([
            ':table_id' => $tableId
        ]);

        $stmt = $pdo->prepare("
            UPDATE carts
            SET status = 'CLOSED'
            WHERE table_id = :table_id
            AND status = 'OPEN'
        ");

        $stmt->execute([
            ':table_id' => $tableId
        ]);

        updateTableStatus(
            $pdo,
            $tableId,
            'AVAILABLE'
        );

        $pdo->commit();

    } catch (Throwable $exception) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Error releasing table ' .
            $tableId .
            ': ' .
            $exception->getMessage()
        );

        throw $exception;
    }
}

/*
|--------------------------------------------------------------------------
| REMOVE DINING TABLE
|--------------------------------------------------------------------------
*/

function removeDiningTable(int $tableId): void
{
    $pdo = getDatabaseConnection();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT id, status
            FROM dining_tables
            WHERE id = :table_id
            FOR UPDATE
        ");

        $stmt->execute([
            ':table_id' => $tableId
        ]);

        $table = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$table) {
            throw new Exception('Table not found.');
        }

        if ($table['status'] === 'OCCUPIED') {
            throw new Exception(
                'An occupied table cannot be removed.'
            );
        }

        // Delete all cart items associated with this table.
        $stmt = $pdo->prepare("
            DELETE ci
            FROM cart_items ci
            INNER JOIN carts c ON c.id = ci.cart_id
            WHERE c.table_id = :table_id
        ");

        $stmt->execute([
            ':table_id' => $tableId
        ]);

        // Delete the table's carts.
        $stmt = $pdo->prepare("
            DELETE FROM carts
            WHERE table_id = :table_id
        ");

        $stmt->execute([
            ':table_id' => $tableId
        ]);

        // Remove the table.
        $stmt = $pdo->prepare("
            DELETE FROM dining_tables
            WHERE id = :table_id
        ");

        $stmt->execute([
            ':table_id' => $tableId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new Exception('Unable to remove table.');
        }

        $pdo->commit();

    } catch (Throwable $exception) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Error removing table ' .
            $tableId .
            ': ' .
            $exception->getMessage()
        );

        throw $exception;
    }
}
