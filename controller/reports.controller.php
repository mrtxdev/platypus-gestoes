<?php

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| DATE RANGE
|--------------------------------------------------------------------------
*/

function reportsGetDateRange(): array
{
    $today = new DateTimeImmutable('today');

    $defaultStart = $today->modify('first day of this month')
        ->format('Y-m-d');

    $defaultEnd = $today->format('Y-m-d');

    $startDate = trim((string) ($_GET['start_date'] ?? $defaultStart));
    $endDate = trim((string) ($_GET['end_date'] ?? $defaultEnd));

    $start = DateTimeImmutable::createFromFormat('!Y-m-d', $startDate);
    $end = DateTimeImmutable::createFromFormat('!Y-m-d', $endDate);

    if (
        !$start
        || !$end
        || $start->format('Y-m-d') !== $startDate
        || $end->format('Y-m-d') !== $endDate
        || $start > $end
    ) {
        return [
            'start_date' => $defaultStart,
            'end_date' => $defaultEnd
        ];
    }

    return [
        'start_date' => $startDate,
        'end_date' => $endDate
    ];
}

/*
|--------------------------------------------------------------------------
| FORMAT MONEY
|--------------------------------------------------------------------------
*/

function reportsFormatMoney(float|int|string|null $value): string
{
    return 'R$ ' . number_format(
        (float) ($value ?? 0),
        2,
        ',',
        '.'
    );
}

/*
|--------------------------------------------------------------------------
| LOAD REPORT DATA
|--------------------------------------------------------------------------
*/

function reportsLoadData(): array
{
    $database = getDatabaseConnection();

    $dates = reportsGetDateRange();

    $startDate = $dates['start_date'];
    $endDate = $dates['end_date'];

    $data = [
        'dates' => $dates,
        'orders' => [],
        'products' => [],
        'expenses' => [],
        'stock_movements' => []
    ];

   

   
    /*
    |--------------------------------------------------------------------------
    | 3. ORDER STATUS SUMMARY
    |--------------------------------------------------------------------------
    */

    $statement = $database->prepare(
        "SELECT
            status,
            COUNT(*) AS total

         FROM orders

         WHERE created_at >= :start_date
         AND created_at < DATE_ADD(:end_date, INTERVAL 1 DAY)

         GROUP BY status"
    );

    $statement->execute([
        'start_date' => $startDate,
        'end_date' => $endDate
    ]);

    $data['orders'] = $statement->fetchAll();

    
    /*
    |--------------------------------------------------------------------------
    | 5. BEST-SELLING PRODUCTS
    |--------------------------------------------------------------------------
    */

    $statement = $database->prepare(
        "SELECT
            oi.product_name,
            SUM(oi.quantity) AS quantity_sold,
            SUM(oi.quantity * oi.unit_price) AS total_revenue

         FROM order_items oi

         INNER JOIN orders o
            ON o.id = oi.order_id

         WHERE o.created_at >= :start_date
         AND o.created_at < DATE_ADD(:end_date, INTERVAL 1 DAY)
         AND o.status <> 'CANCELLED'

         GROUP BY oi.product_name

         ORDER BY quantity_sold DESC"
    );

    $statement->execute([
        'start_date' => $startDate,
        'end_date' => $endDate
    ]);

    $data['products'] = $statement->fetchAll();

   
   
    /*
    |--------------------------------------------------------------------------
    | 8. INGREDIENT STOCK MOVEMENTS
    |--------------------------------------------------------------------------
    */

    $statement = $database->prepare(
        "SELECT
            'Ingrediente' AS item_type,
            i.name AS item_name,
            m.type,
            m.quantity,
            m.reason,
            m.created_at,
            u.name AS registered_by

         FROM inventory_movements m

         INNER JOIN ingredients i
            ON i.id = m.ingredient_id

         LEFT JOIN users u
            ON u.id = m.user_id

         WHERE m.created_at >= :start_date
         AND m.created_at < DATE_ADD(:end_date, INTERVAL 1 DAY)"
    );

    $statement->execute([
        'start_date' => $startDate,
        'end_date' => $endDate
    ]);

    $ingredientMovements = $statement->fetchAll();

    /*
    |--------------------------------------------------------------------------
    | 9. PRODUCT STOCK MOVEMENTS
    |--------------------------------------------------------------------------
    */

    $statement = $database->prepare(
        "SELECT
            'Produto' AS item_type,
            p.name AS item_name,
            m.type,
            m.quantity,
            m.reason,
            m.created_at,
            u.name AS registered_by

         FROM product_stock_movements m

         LEFT JOIN products p
            ON p.id = m.product_id

         LEFT JOIN users u
            ON u.id = m.user_id

         WHERE m.created_at >= :start_date
         AND m.created_at < DATE_ADD(:end_date, INTERVAL 1 DAY)"
    );

    $statement->execute([
        'start_date' => $startDate,
        'end_date' => $endDate
    ]);

    $productMovements = $statement->fetchAll();

    /*
    |--------------------------------------------------------------------------
    | 10. MERGE STOCK MOVEMENTS
    |--------------------------------------------------------------------------
    */

    $data['stock_movements'] = array_merge(
        $ingredientMovements,
        $productMovements
    );

    usort(
        $data['stock_movements'],
        static fn(array $a, array $b): int =>
            strcmp($b['created_at'], $a['created_at'])
    );

    return $data;
}

/*
|--------------------------------------------------------------------------
| REGISTER EXPENSE
|--------------------------------------------------------------------------
*/

function reportsRegisterExpense(array $input): void
{
    $description = trim((string) ($input['description'] ?? ''));
    $category = trim((string) ($input['category'] ?? ''));

    $amount = str_replace(
        ',',
        '.',
        trim((string) ($input['amount'] ?? ''))
    );

    $expenseDate = trim((string) ($input['expense_date'] ?? ''));

    if (
        $description === ''
        || mb_strlen($description) > 255
        || $category === ''
        || mb_strlen($category) > 100
    ) {
        throw new InvalidArgumentException(
            'Informe uma descrição e uma categoria válidas.'
        );
    }

    if (
        !is_numeric($amount)
        || (float) $amount <= 0
        || (float) $amount > 99999999.99
    ) {
        throw new InvalidArgumentException(
            'Informe um valor de despesa válido.'
        );
    }

    $date = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $expenseDate
    );

    if (!$date || $date->format('Y-m-d') !== $expenseDate) {
        throw new InvalidArgumentException(
            'Informe uma data válida.'
        );
    }

    $database = getDatabaseConnection();

    $statement = $database->prepare(
        "INSERT INTO expenses (
            description,
            category,
            amount,
            expense_date,
            user_id
        ) VALUES (
            :description,
            :category,
            :amount,
            :expense_date,
            :user_id
        )"
    );

    $statement->execute([
        'description' => $description,
        'category' => $category,
        'amount' => number_format((float) $amount, 2, '.', ''),
        'expense_date' => $expenseDate,
        'user_id' => $_SESSION['user_id'] ?? null
    ]);
}

/*
|--------------------------------------------------------------------------
| CSV EXPORT
|--------------------------------------------------------------------------
*/

function reportsExportCsv(string $type): void
{
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');

    $allowedTypes = [
        'products',
        'expenses',
        'stock'
    ];

    if (!in_array($type, $allowedTypes, true)) {
        http_response_code(400);
        exit('Tipo de exportação inválido.');
    }

    $data = reportsLoadData();

    $exports = [

        /*
        |--------------------------------------------------------------------------
        | PRODUCTS
        |--------------------------------------------------------------------------
        */

        'products' => [
            'filename' => 'produtos',

            'headers' => [
                'Produto',
                'Quantidade vendida',
                'Faturamento'
            ],

            'rows' => array_map(
                static fn(array $item): array => [
                    $item['product_name'],
                    $item['quantity_sold'],
                    $item['total_revenue']
                ],
                $data['products']
            )
        ],

        /*
        |--------------------------------------------------------------------------
        | EXPENSES
        |--------------------------------------------------------------------------
        */

        'expenses' => [
            'filename' => 'despesas',

            'headers' => [
                'Descrição',
                'Categoria',
                'Valor',
                'Data',
                'Registrado por'
            ],

            'rows' => array_map(
                static fn(array $item): array => [
                    $item['description'],
                    $item['category'],
                    $item['amount'],
                    $item['expense_date'],
                    $item['registered_by']
                ],
                $data['expenses']
            )
        ],

        /*
        |--------------------------------------------------------------------------
        | STOCK
        |--------------------------------------------------------------------------
        */

        'stock' => [
            'filename' => 'estoque',

            'headers' => [
                'Tipo de item',
                'Item',
                'Movimentação',
                'Quantidade',
                'Motivo',
                'Data',
                'Registrado por'
            ],

            'rows' => array_map(
                static fn(array $item): array => [
                    $item['item_type'],
                    $item['item_name'],
                    $item['type'],
                    $item['quantity'],
                    $item['reason'],
                    $item['created_at'],
                    $item['registered_by']
                ],
                $data['stock_movements']
            )
        ]
    ];

    $export = $exports[$type];

    $filename = $export['filename']
        . '_'
        . $data['dates']['start_date']
        . '_'
        . $data['dates']['end_date']
        . '.csv';

    /*
    |--------------------------------------------------------------------------
    | CLEAR OUTPUT BUFFERS
    |--------------------------------------------------------------------------
    */

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    /*
    |--------------------------------------------------------------------------
    | CSV HEADERS
    |--------------------------------------------------------------------------
    */

    header('Content-Type: text/csv; charset=UTF-8');

    header(
        'Content-Disposition: attachment; filename="' . $filename . '"'
    );

    header('Cache-Control: no-store, no-cache, must-revalidate');

    header('Pragma: no-cache');

    /*
    |--------------------------------------------------------------------------
    | OPEN OUTPUT
    |--------------------------------------------------------------------------
    */

    $output = fopen('php://output', 'w');

    if ($output === false) {
        throw new RuntimeException(
            'Não foi possível gerar o arquivo CSV.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UTF-8 BOM
    |--------------------------------------------------------------------------
    */

    fwrite($output, "\xEF\xBB\xBF");

    /*
    |--------------------------------------------------------------------------
    | WRITE CSV HEADER
    |--------------------------------------------------------------------------
    */

    fputcsv(
        $output,
        $export['headers'],
        ';',
        '"',
        ''
    );

    /*
    |--------------------------------------------------------------------------
    | WRITE CSV ROWS
    |--------------------------------------------------------------------------
    */

    foreach ($export['rows'] as $row) {
        fputcsv(
            $output,
            $row,
            ';',
            '"',
            ''
        );
    }

    fclose($output);

    exit;
}
