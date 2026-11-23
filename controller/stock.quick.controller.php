<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function quickStockUserId(): ?int
{
    $value = filter_var(
        $_SESSION['user_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    return $value !== false && $value !== null && $value > 0
        ? (int) $value
        : null;
}

function getQuickStockData(): array
{
    $pdo = getDatabaseConnection();

    $ingredients = $pdo->query(
        "SELECT
            id,
            name,
            unit,
            current_quantity,
            minimum_quantity,
            is_active,
            CASE
                WHEN current_quantity <= 0 THEN 'EMPTY'
                WHEN current_quantity <= minimum_quantity THEN 'LOW'
                ELSE 'NORMAL'
            END AS stock_status
         FROM ingredients
         WHERE is_active = 1
         ORDER BY
            CASE
                WHEN current_quantity <= 0 THEN 1
                WHEN current_quantity <= minimum_quantity THEN 2
                ELSE 3
            END,
            name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $products = $pdo->query(
        "SELECT
            id,
            name,
            stock_quantity,
            is_available,
            CASE
                WHEN stock_quantity <= 0 THEN 'EMPTY'
                ELSE 'NORMAL'
            END AS stock_status
         FROM products
         WHERE stock_quantity IS NOT NULL
         ORDER BY
            CASE WHEN stock_quantity <= 0 THEN 1 ELSE 2 END,
            name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $history = $pdo->query(
        "SELECT *
         FROM (
            SELECT
                im.id,
                'INGREDIENT' AS stock_type,
                i.name AS item_name,
                im.type,
                im.quantity,
                i.unit AS unit,
                im.reason,
                im.created_at,
                u.name AS user_name
            FROM inventory_movements im
            INNER JOIN ingredients i ON i.id = im.ingredient_id
            LEFT JOIN users u ON u.id = im.user_id

            UNION ALL

            SELECT
                pm.id,
                'PRODUCT' AS stock_type,
                p.name AS item_name,
                pm.type,
                pm.quantity,
                'un' AS unit,
                pm.reason,
                pm.created_at,
                u.name AS user_name
            FROM product_stock_movements pm
            LEFT JOIN products p ON p.id = pm.product_id
            LEFT JOIN users u ON u.id = pm.user_id
         ) AS movements
         ORDER BY created_at DESC
         LIMIT 30"
    )->fetchAll(PDO::FETCH_ASSOC);

    $ingredientStats = [
        'total' => count($ingredients),
        'low' => 0,
        'empty' => 0
    ];

    foreach ($ingredients as $ingredient) {
        if ($ingredient['stock_status'] === 'EMPTY') {
            $ingredientStats['empty']++;
        } elseif ($ingredient['stock_status'] === 'LOW') {
            $ingredientStats['low']++;
        }
    }

    $productStats = [
        'total' => count($products),
        'empty' => 0
    ];

    foreach ($products as $product) {
        if ($product['stock_status'] === 'EMPTY') {
            $productStats['empty']++;
        }
    }

    return [
        'ingredients' => $ingredients,
        'products' => $products,
        'history' => $history,
        'stats' => [
            'ingredients' => $ingredientStats,
            'products' => $productStats
        ]
    ];
}


function registerQuickStockEntry(
    PDO $pdo,
    string $type,
    int $itemId,
    float $quantity,
    string $reason
): void {
    if ($quantity <= 0) {
        throw new InvalidArgumentException(
            'A quantidade da entrada deve ser maior que zero.'
        );
    }

    if ($reason === '' || mb_strlen($reason) > 246) {
        throw new InvalidArgumentException(
            'Informe uma justificativa de até 246 caracteres.'
        );
    }

    $userId = quickStockUserId();

    if ($type === 'ingredient') {
        if ($quantity > 999999999.999) {
            throw new InvalidArgumentException(
                'A quantidade informada ultrapassa o limite permitido.'
            );
        }

        $stmt = $pdo->prepare(
            'SELECT id, current_quantity
             FROM ingredients
             WHERE id = :id AND is_active = 1
             FOR UPDATE'
        );

        $stmt->execute(['id' => $itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new RuntimeException(
                'Ingrediente não encontrado ou inativo.'
            );
        }

        $currentQuantity = (float) $item['current_quantity'];
        $newQuantity = $currentQuantity + $quantity;

        if ($newQuantity > 999999999.999) {
            throw new InvalidArgumentException(
                'A quantidade final ultrapassa o limite permitido.'
            );
        }

        $update = $pdo->prepare(
            'UPDATE ingredients
             SET current_quantity = :quantity
             WHERE id = :id'
        );

        $update->execute([
            'quantity' => number_format($newQuantity, 3, '.', ''),
            'id' => $itemId
        ]);

        $movement = $pdo->prepare(
            "INSERT INTO inventory_movements
                (ingredient_id, user_id, type, quantity, reason)
             VALUES
                (:item_id, :user_id, 'IN', :quantity, :reason)"
        );

        $movement->execute([
            'item_id' => $itemId,
            'user_id' => $userId,
            'quantity' => number_format($quantity, 3, '.', ''),
            'reason' => 'Entrada: ' . $reason
        ]);

    } elseif ($type === 'product') {
        if (
            floor($quantity) !== $quantity
            || $quantity > 4294967295
        ) {
            throw new InvalidArgumentException(
                'A quantidade de produtos deve ser um número inteiro válido.'
            );
        }

        $stmt = $pdo->prepare(
            'SELECT id, stock_quantity
             FROM products
             WHERE id = :id AND stock_quantity IS NOT NULL
             FOR UPDATE'
        );

        $stmt->execute(['id' => $itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new RuntimeException(
                'Produto não encontrado ou sem controle de estoque próprio.'
            );
        }

        $currentQuantity = (int) $item['stock_quantity'];
        $newQuantity = $currentQuantity + (int) $quantity;

        if ($newQuantity > 4294967295) {
            throw new InvalidArgumentException(
                'A quantidade final ultrapassa o limite permitido.'
            );
        }

        $update = $pdo->prepare(
            'UPDATE products
             SET stock_quantity = :quantity
             WHERE id = :id'
        );

        $update->execute([
            'quantity' => $newQuantity,
            'id' => $itemId
        ]);

        $movement = $pdo->prepare(
            "INSERT INTO product_stock_movements
                (product_id, user_id, type, quantity, reason)
             VALUES
                (:item_id, :user_id, 'IN', :quantity, :reason)"
        );

        $movement->execute([
            'item_id' => $itemId,
            'user_id' => $userId,
            'quantity' => (int) $quantity,
            'reason' => 'Entrada: ' . $reason
        ]);

    } else {
        throw new InvalidArgumentException(
            'Tipo de estoque inválido.'
        );
    }
}


function registerQuickStockLoss(
    PDO $pdo,
    string $type,
    int $itemId,
    float $quantity,
    string $reason
): void {
    if ($quantity <= 0) {
        throw new InvalidArgumentException(
            'A quantidade deve ser maior que zero.'
        );
    }

    if ($reason === '' || mb_strlen($reason) > 248) {
        throw new InvalidArgumentException(
            'Informe uma justificativa de até 248 caracteres.'
        );
    }

    $userId = quickStockUserId();

    if ($type === 'ingredient') {
        $stmt = $pdo->prepare(
            'SELECT id, current_quantity
             FROM ingredients
             WHERE id = :id AND is_active = 1
             FOR UPDATE'
        );

        $stmt->execute(['id' => $itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new RuntimeException(
                'Ingrediente não encontrado ou inativo.'
            );
        }

        if ((float) $item['current_quantity'] < $quantity) {
            throw new RuntimeException(
                'Estoque insuficiente para registrar essa perda.'
            );
        }

        $update = $pdo->prepare(
            'UPDATE ingredients
             SET current_quantity = current_quantity - :quantity
             WHERE id = :id'
        );

        $update->execute([
            'quantity' => number_format($quantity, 3, '.', ''),
            'id' => $itemId
        ]);

        $movement = $pdo->prepare(
            "INSERT INTO inventory_movements
                (ingredient_id, user_id, type, quantity, reason)
             VALUES
                (:item_id, :user_id, 'OUT', :quantity, :reason)"
        );

    } elseif ($type === 'product') {
        if (floor($quantity) !== $quantity) {
            throw new InvalidArgumentException(
                'A quantidade de produtos deve ser um número inteiro.'
            );
        }

        $stmt = $pdo->prepare(
            'SELECT id, stock_quantity
             FROM products
             WHERE id = :id AND stock_quantity IS NOT NULL
             FOR UPDATE'
        );

        $stmt->execute(['id' => $itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new RuntimeException(
                'Produto não encontrado ou sem controle de estoque próprio.'
            );
        }

        if ((int) $item['stock_quantity'] < (int) $quantity) {
            throw new RuntimeException(
                'Estoque insuficiente para registrar essa perda.'
            );
        }

        $update = $pdo->prepare(
            'UPDATE products
             SET stock_quantity = stock_quantity - :quantity
             WHERE id = :id'
        );

        $update->execute([
            'quantity' => (int) $quantity,
            'id' => $itemId
        ]);

        $movement = $pdo->prepare(
            "INSERT INTO product_stock_movements
                (product_id, user_id, type, quantity, reason)
             VALUES
                (:item_id, :user_id, 'OUT', :quantity, :reason)"
        );

    } else {
        throw new InvalidArgumentException(
            'Tipo de estoque inválido.'
        );
    }

    $movement->execute([
        'item_id' => $itemId,
        'user_id' => $userId,
        'quantity' => $quantity,
        'reason' => 'Perda: ' . $reason
    ]);
}


function adjustQuickStock(
    PDO $pdo,
    string $type,
    int $itemId,
    float $newQuantity,
    string $reason
): void {
    if ($newQuantity < 0) {
        throw new InvalidArgumentException(
            'A quantidade não pode ser negativa.'
        );
    }

    if ($reason === '' || mb_strlen($reason) > 255) {
        throw new InvalidArgumentException(
            'Informe uma justificativa de até 255 caracteres.'
        );
    }

    $userId = quickStockUserId();

    if ($type === 'ingredient') {
        $stmt = $pdo->prepare(
            'SELECT id, current_quantity
             FROM ingredients
             WHERE id = :id AND is_active = 1
             FOR UPDATE'
        );

        $stmt->execute(['id' => $itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new RuntimeException(
                'Ingrediente não encontrado ou inativo.'
            );
        }

        $oldQuantity = (float) $item['current_quantity'];
        $difference = abs($newQuantity - $oldQuantity);

        if ($difference > 0.000001) {
            $update = $pdo->prepare(
                'UPDATE ingredients
                 SET current_quantity = :quantity
                 WHERE id = :id'
            );

            $update->execute([
                'quantity' => number_format($newQuantity, 3, '.', ''),
                'id' => $itemId
            ]);

            $movement = $pdo->prepare(
                "INSERT INTO inventory_movements
                    (ingredient_id, user_id, type, quantity, reason)
                 VALUES
                    (:item_id, :user_id, 'ADJUSTMENT', :quantity, :reason)"
            );

            $movement->execute([
                'item_id' => $itemId,
                'user_id' => $userId,
                'quantity' => number_format($difference, 3, '.', ''),
                'reason' => 'Ajuste: ' . $reason
                    . ' | De ' . number_format($oldQuantity, 3, ',', '.')
                    . ' para ' . number_format($newQuantity, 3, ',', '.')
            ]);
        }

    } elseif ($type === 'product') {
        if (
            floor($newQuantity) !== $newQuantity
            || $newQuantity > 4294967295
        ) {
            throw new InvalidArgumentException(
                'A quantidade de produtos deve ser um número inteiro válido.'
            );
        }

        $stmt = $pdo->prepare(
            'SELECT id, stock_quantity
             FROM products
             WHERE id = :id AND stock_quantity IS NOT NULL
             FOR UPDATE'
        );

        $stmt->execute(['id' => $itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new RuntimeException(
                'Produto não encontrado ou sem controle de estoque próprio.'
            );
        }

        $oldQuantity = (int) $item['stock_quantity'];
        $difference = abs((int) $newQuantity - $oldQuantity);

        if ($difference > 0) {
            $update = $pdo->prepare(
                'UPDATE products
                 SET stock_quantity = :quantity
                 WHERE id = :id'
            );

            $update->execute([
                'quantity' => (int) $newQuantity,
                'id' => $itemId
            ]);

            $movement = $pdo->prepare(
                "INSERT INTO product_stock_movements
                    (product_id, user_id, type, quantity, reason)
                 VALUES
                    (:item_id, :user_id, 'ADJUSTMENT', :quantity, :reason)"
            );

            $movement->execute([
                'item_id' => $itemId,
                'user_id' => $userId,
                'quantity' => $difference,
                'reason' => 'Ajuste: ' . $reason
                    . ' | De ' . $oldQuantity
                    . ' para ' . (int) $newQuantity
            ]);
        }

    } else {
        throw new InvalidArgumentException(
            'Tipo de estoque inválido.'
        );
    }
}


function processQuickStockAction(array $data): void
{
    $action = (string) ($data['action'] ?? '');
    $type = (string) ($data['stock_type'] ?? '');

    $itemId = filter_var(
        $data['item_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($itemId === false || $itemId === null) {
        throw new InvalidArgumentException(
            'Selecione um item válido.'
        );
    }

    $quantityRaw = str_replace(
        ',',
        '.',
        trim((string) ($data['quantity'] ?? ''))
    );

    if (!is_numeric($quantityRaw)) {
        throw new InvalidArgumentException(
            'Informe uma quantidade válida.'
        );
    }

    $quantity = (float) $quantityRaw;
    $reason = trim((string) ($data['reason'] ?? ''));

    $pdo = getDatabaseConnection();
    $pdo->beginTransaction();

    try {
        if ($action === 'register-entry') {
            registerQuickStockEntry(
                $pdo,
                $type,
                (int) $itemId,
                $quantity,
                $reason
            );

        } elseif ($action === 'register-loss') {
            registerQuickStockLoss(
                $pdo,
                $type,
                (int) $itemId,
                $quantity,
                $reason
            );

        } elseif ($action === 'adjust-stock') {
            adjustQuickStock(
                $pdo,
                $type,
                (int) $itemId,
                $quantity,
                $reason
            );

        } else {
            throw new InvalidArgumentException(
                'Operação de estoque inválida.'
            );
        }

        $pdo->commit();

    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}
