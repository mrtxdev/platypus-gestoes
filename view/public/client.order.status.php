
<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controller/client.menu.controller.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

try {
    $token = trim((string) ($_GET['mesa'] ?? ''));

    if (!preg_match('/\A[a-f0-9]{36}\z/i', $token)) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid table token.'
        ]);

        exit;
    }

    $database = getDatabaseConnection();

    $statement = $database->prepare("
        SELECT id
        FROM dining_tables
        WHERE qr_token = :token
        AND status != 'INACTIVE'
        LIMIT 1
    ");

    $statement->execute([
        ':token' => $token
    ]);

    $table = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$table) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Dining table not found.'
        ]);

        exit;
    }

    $orders = getClientOrdersByTableId(
        $database,
        (int) $table['id']
    );

    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);

} catch (Throwable $exception) {
    error_log(
        'Client order status error: '
        . $exception->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to retrieve order status.'
    ]);
}
