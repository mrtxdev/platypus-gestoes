<?php

error_reporting(E_ALL);

$isAjaxRequest = (
    strtolower(trim((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''))) === 'xmlhttprequest'
    || str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json')
);

if ($isAjaxRequest) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}

ob_start();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function sendCartJsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $json = json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    if ($json === false) {
        http_response_code(500);
        $json = '{"success":false,"message":"Erro ao gerar resposta do servidor."}';
    }

    echo $json;
    exit;
}

function routerCsrfIsValid(): bool
{
    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');

    return is_string($submittedToken)
    && $sessionToken !== ''
    && hash_equals($sessionToken, $submittedToken);
}

require_once __DIR__ . '/controller/user.controller.php';


/* 1. GET PAGE AND ACTION */

$page = strtolower(trim((string) ($_GET['pagina'] ?? $_GET['page'] ?? 'dashboard')));
$page = str_replace('_', '-', $page);

$action = strtolower(trim((string) ($_POST['action'] ?? $_GET['action'] ?? '')));

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';


/* 1.1 IDENTIFY DINING TABLE THROUGH QR CODE */

if (in_array($page, ['cardapio', 'carrinho'], true) && isset($_GET['mesa'])) {

    $tableToken = trim((string) $_GET['mesa']);

    unset($_SESSION['table_id']);

    if (strlen($tableToken) === 36 && ctype_xdigit($tableToken)) {

        try {

            require_once __DIR__ . '/config/database.php';

            $database = getDatabaseConnection();

            $statement = $database->prepare(
                "SELECT id
                FROM dining_tables
                WHERE qr_token = :token
                AND status <> 'INACTIVE'
                LIMIT 1"
            );

            $statement->execute([
                'token' => $tableToken
            ]);

            $tableId = $statement->fetchColumn();

            if ($tableId !== false) {
                $_SESSION['table_id'] = (int) $tableId;
            }

        } catch (Throwable $exception) {

            error_log(
                'Dining table identification failed: '
                . $exception->getMessage()
            );

            unset($_SESSION['table_id']);
        }
    }
}


/* 2. REGISTER PAGE ROUTES */

$routes = [

    'login' => [
        'file' => 'view/public/admin/login.admin.php',
        'public' => true,
        'roles' => null,
        'title' => 'Login'
    ],

    'dashboard' => [
        'file' => 'view/public/admin/dashboard.admin.php',
        'public' => false,
        'roles' => ['ADMIN', 'KITCHEN', 'ATTENDANT'],
        'title' => 'Dashboard'
    ],

    'novo-usuario' => [
        'file' => 'view/public/admin/new.user.admin.php',
        'public' => true,
        'roles' => 'ADMIN',
        'title' => 'Novo Usuário'
    ],

    'gerar-qrcode' => [
        'file' => 'view/public/admin/generate.qrcode.admin.php',
        'public' => false,
        'roles' => 'ADMIN',
        'title' => 'Gerar QR-Code'
    ],

    'cardapio' => [
        'file' => 'view/public/client/menu.client.php',
        'public' => true,
        'roles' => null,
        'title' => 'Cardápio'
    ],

    'carrinho' => [
        'file' => 'view/public/client/cart.client.php',
        'public' => true,
        'roles' => null,
        'title' => 'Carrinho'
    ],

    'cardapio/admin' => [
        'file' => 'view/public/admin/menu.admin.php',
        'public' => false,
        'roles' => 'ADMIN',
        'title' => 'Gerenciar Cardápio'
    ],

    'cardapio/admin/adicionar' => [
        'file' => 'view/public/admin/add.menu.admin.php',
        'public' => false,
        'roles' => 'ADMIN',
        'title' => 'Adicionar produto'
    ],

    'categorias/adicionar' => [
        'file' => 'view/public/admin/add.category.admin.php',
        'public' => false,
        'roles' => 'ADMIN',
        'title' => 'Adicionar categoria'
    ],

    'cardapio/admin/editar' => [
        'file' => 'view/public/admin/edit.menu.admin.php',
        'public' => false,
        'roles' => 'ADMIN',
        'title' => 'Editar produto'
    ],

    'ingredientes' => [
        'file' => 'view/public/admin/ingredients.admin.php',
        'public' => false,
        'roles' => 'ADMIN',
        'title' => 'Ingredientes e Estoque'
    ],

    'mesas' => [
        'file' => 'view/public/admin/table.admin.php',
        'public' => false,
        'roles' => ['ADMIN', 'ATTENDANT'],
        'title' => 'Mesas'
    ],

    'relatorios' => [
        'file' => 'view/public/admin/reports.admin.php',
        'public' => false,
        'roles' => 'ADMIN',
        'title' => 'Relatórios'
    ],

    'monitor-cozinha' => [
        'file' => 'view/public/admin/monitor.kitchen.admin.php',
        'public' => false,
        'roles' => 'KITCHEN',
        'title' => 'Monitor de Pedidos'
    ],

    'estoque-rapido' => [
        'file' => 'view/public/admin/stock.quick.admin.php',
        'public' => false,
        'roles' => 'KITCHEN',
        'title' => 'Estoque Rápido'
    ],

    'pedidos-prontos' => [
        'file' => 'view/public/admin/ready.orders.admin.php',
        'public' => false,
        'roles' => 'ATTENDANT',
        'title' => 'Pedidos Prontos'
    ],

    'chamados-mesa' => [
        'file' => 'view/public/admin/table.admin.php',
        'public' => false,
        'roles' => 'ATTENDANT',
        'title' => 'Chamados das Mesas'
    ]

];


/* 3. VALIDATE ROUTE */
if (!isset($routes[$page])) {
    require_once __DIR__ . '//view/public/error/404.php';
    exit;
}

$currentRoute = $routes[$page];



/* 4. AUTHENTICATION */

if (!$currentRoute['public'] && !isAuthenticated()) {

    header('Location: /platypus_gestoes/router.php?pagina=login');

    exit;
}


/* 5. PERMISSION VALIDATION */

if (!$currentRoute['public']) {

    $allowedRoles = $currentRoute['roles'];

    $hasPermission = false;

    if (is_array($allowedRoles)) {

        foreach ($allowedRoles as $role) {

            if (hasRole($role)) {
                $hasPermission = true;
                break;
            }
        }

    } elseif ($allowedRoles === null) {

        $hasPermission = true;

    } else {

        $hasPermission = hasRole($allowedRoles);
    }

    if (!$hasPermission) {

        http_response_code(403);

        exit('403 - Você não tem permissão para acessar esta página.');
    }
}


/* 6. ROUTE REQUESTS TO CONTROLLERS */


/* Category registration */
/* REPORTS */

if (
    $page === 'relatorios'
    && $action === 'export-csv'
    && $requestMethod === 'GET'
) {
    require_once __DIR__ . '/controller/reports.controller.php';

    reportsExportCsv(
        (string) ($_GET['type'] ?? '')
    );

    exit;
}

if (
    $page === 'relatorios'
    && $action === 'register-expense'
    && $requestMethod === 'POST'
) {
    require_once __DIR__ . '/controller/reports.controller.php';

    try {
        if (!routerCsrfIsValid()) {
            throw new RuntimeException(
                'Sessão expirada. Atualize a página e tente novamente.'
            );
        }

        reportsRegisterExpense($_POST);

        $_SESSION['reports_message'] =
            'Despesa registrada com sucesso.';

        $_SESSION['reports_success'] = true;

    } catch (Throwable $exception) {
        error_log(
            'Expense registration error: '
            . $exception->getMessage()
        );

        $_SESSION['reports_message'] =
            $exception->getMessage();

        $_SESSION['reports_success'] = false;
    }

    $redirectParams = [
        'pagina' => 'relatorios'
    ];

    foreach (['start_date', 'end_date'] as $dateField) {
        $dateValue = $_POST[$dateField] ?? '';

        if (
            is_string($dateValue)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)
        ) {
            $redirectParams[$dateField] = $dateValue;
        }
    }

    header(
        'Location: /platypus_gestoes/router.php?'
        . http_build_query($redirectParams)
    );

    exit;
}
if (
    $page === 'categorias/adicionar'
    && $action === 'register-category'
    && $requestMethod === 'POST'
) {

    require_once __DIR__ . '/controller/category.controller.php';

    add();

    exit;
}


/* Product registration */

if (
    $page === 'cardapio/admin/adicionar'
    && $action === 'register-product'
    && $requestMethod === 'POST'
) {

    require_once __DIR__ . '/controller/product.controller.php';

    if (!routerCsrfIsValid()) {
        redirectProduct('Sessão expirada. Atualize a página e tente novamente.');
    }

    registerProduct();

    exit;
}


/* Product update */

if (
    $page === 'cardapio/admin/editar'
    && $action === 'update-product'
    && $requestMethod === 'POST'
) {

    require_once __DIR__ . '/controller/product.controller.php';

    if (!routerCsrfIsValid()) {
        redirectProduct('Sessão expirada. Atualize a página e tente novamente.');
    }

    updateProduct();

    exit;
}


/* Product deletion */

if (
    $page === 'cardapio/admin'
    && $action === 'delete-product'
    && $requestMethod === 'POST'
) {

    require_once __DIR__ . '/controller/product.controller.php';

    if (!routerCsrfIsValid()) {
        redirectProduct('Sessão expirada. Atualize a página e tente novamente.');
    }

    deleteProduct();

    exit;
}


/* Ingredient management */

if (
    $page === 'ingredientes'
    && $requestMethod === 'POST'
    && in_array($action, [
        'register-ingredient',
        'update-ingredient',
        'adjust-ingredient-stock',
        'delete-ingredient'
    ], true)
) {

    require_once __DIR__ . '/controller/ingredient.controller.php';

    if (!routerCsrfIsValid()) {
        redirectIngredients('Sessão expirada. Atualize a página e tente novamente.');
    }

    switch ($action) {

        case 'register-ingredient':
        registerIngredient();
        break;

        case 'update-ingredient':
        updateIngredient();
        break;

        case 'adjust-ingredient-stock':
        adjustIngredientStock();
        break;

        case 'delete-ingredient':
        deleteIngredient();
        break;
    }

    exit;
}


/* Quick stock management */

if (
    $page === 'estoque-rapido'
    && $requestMethod === 'POST'
    && in_array($action, ['register-loss', 'adjust-stock'], true)
) {

    require_once __DIR__ . '/controller/stock.quick.controller.php';

    try {

        if (!routerCsrfIsValid()) {
            throw new RuntimeException(
                'Sessão expirada. Atualize a página e tente novamente.'
            );
        }

        /*
         * The view sends stock_id.
         * The controller expects item_id.
         */
        $stockData = $_POST;

        $stockData['item_id'] = $_POST['stock_id'] ?? null;

        processQuickStockAction($stockData);

        $_SESSION['quick_stock_message'] = $action === 'register-loss'
        ? 'Perda registrada com sucesso.'
        : 'Estoque ajustado com sucesso.';

        $_SESSION['quick_stock_success'] = true;

    } catch (Throwable $exception) {

        error_log(
            'Quick stock operation error: '
            . $exception->getMessage()
        );

        $_SESSION['quick_stock_message'] = $exception->getMessage();
        $_SESSION['quick_stock_success'] = false;
    }

    header('Location: /platypus_gestoes/router.php?pagina=estoque-rapido');

    exit;
}


/* QR-Code generation */

if (
    $page === 'gerar-qrcode'
    && $action === 'generate-qr-code'
    && $requestMethod === 'POST'
) {

    require_once __DIR__ . '/controller/generate.qrcode.controller.php';

    generateQrCode();

    exit;
}


/* QR-Code table deletion */

if (
    $page === 'gerar-qrcode'
    && $action === 'delete-table'
    && $requestMethod === 'POST'
) {

    require_once __DIR__ . '/controller/generate.qrcode.controller.php';

    deleteQrTable();

    exit;
}


/* 6.1 DINING TABLE ACTIONS */


/* Release table */

if (
    $page === 'mesas'
    && $action === 'release-table'
    && $requestMethod === 'POST'
) {

    require_once __DIR__ . '/controller/table.controller.php';

    try {

        if (!routerCsrfIsValid()) {
            throw new RuntimeException('Token CSRF inválido.');
        }

        $tableId = filter_var(
            $_POST['table_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($tableId === false || $tableId === null || $tableId <= 0) {
            throw new InvalidArgumentException('Mesa inválida.');
        }

        releaseDiningTable((int) $tableId);

        $_SESSION['table_message'] = 'Mesa liberada com sucesso.';
        $_SESSION['table_success'] = true;

    } catch (Throwable $exception) {

        error_log('Table release error: ' . $exception->getMessage());

        $_SESSION['table_message'] = 'Não foi possível liberar a mesa.';
        $_SESSION['table_success'] = false;
    }

    header('Location: /platypus_gestoes/router.php?pagina=mesas');

    exit;
}


/* Remove table */

if (
    $page === 'mesas'
    && $action === 'remove-table'
    && $requestMethod === 'POST'
) {

    require_once __DIR__ . '/controller/table.controller.php';

    try {

        if (!routerCsrfIsValid()) {
            throw new RuntimeException('Token CSRF inválido.');
        }

        $tableId = filter_var(
            $_POST['table_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($tableId === false || $tableId === null || $tableId <= 0) {
            throw new InvalidArgumentException('Mesa inválida.');
        }

        removeDiningTable((int) $tableId);

        $_SESSION['table_message'] = 'Mesa removida com sucesso!';
        $_SESSION['table_success'] = true;

    } catch (Throwable $exception) {

        error_log('Table removal error: ' . $exception->getMessage());

        $_SESSION['table_message'] = 'Não foi possível remover a mesa.';
        $_SESSION['table_success'] = false;
    }

    header('Location: /platypus_gestoes/router.php?pagina=mesas');

    exit;
}


/* 6.2 SHOPPING CART ACTIONS */

if (
    in_array($page, ['cardapio', 'carrinho'], true)
    && in_array($action, [
        'add-to-cart',
        'update-cart',
        'remove-cart',
        'checkout-cart'
    ], true)
    && $requestMethod === 'POST'
) {

    require_once __DIR__ . '/controller/cart.controller.php';

    try {

        if (!routerCsrfIsValid()) {
            throw new RuntimeException('Token CSRF inválido.');
        }

        $tableId = (int) ($_SESSION['table_id'] ?? 0);

        if ($tableId <= 0) {
            throw new RuntimeException('Mesa não identificada na sessão.');
        }

        $success = false;

        switch ($action) {

            case 'add-to-cart':

            $productId = filter_var(
                $_POST['product_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $quantity = filter_var(
                $_POST['quantity'] ?? 1,
                FILTER_VALIDATE_INT
            );

            if (
                $productId === false
                || $productId === null
                || $productId <= 0
                || $quantity === false
                || $quantity === null
                || $quantity < 1
                || $quantity > 99
            ) {
                throw new InvalidArgumentException(
                    'Produto ou quantidade inválida.'
                );
            }

            $success = addToCart(
                $tableId,
                (int) $productId,
                (int) $quantity,
                $_POST['removed_ingredients'] ?? []
            );

            break;


            case 'update-cart':

            $cartItemId = filter_var(
                $_POST['cart_item_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $quantity = filter_var(
                $_POST['quantity'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (
                $cartItemId === false
                || $cartItemId === null
                || $cartItemId <= 0
                || $quantity === false
                || $quantity === null
                || $quantity < 1
                || $quantity > 99
            ) {
                throw new InvalidArgumentException(
                    'Item ou quantidade inválida.'
                );
            }

            $success = updateCartItemQuantity(
                (int) $quantity,
                (int) $cartItemId,
                $tableId
            );

            break;


            case 'remove-cart':

            $cartItemId = filter_var(
                $_POST['cart_item_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (
                $cartItemId === false
                || $cartItemId === null
                || $cartItemId <= 0
            ) {
                throw new InvalidArgumentException('Item inválido.');
            }

            $success = removeFromCart(
                (int) $cartItemId,
                $tableId
            );

            break;


            case 'checkout-cart':

            $orderId = checkoutCart($tableId);

            $_SESSION['cart_message'] =
            'Pedido #' . $orderId . ' enviado para a cozinha.';

            $_SESSION['cart_success'] = true;

            $success = true;

            break;
        }

        if (!$success) {
            throw new RuntimeException(
                'A operação do carrinho não foi concluída.'
            );
        }

        if (
            $isAjaxRequest
            && in_array(
                $action,
                ['add-to-cart', 'update-cart', 'remove-cart'],
                true
            )
        ) {

            $cartItems = getTableCart($tableId);

            $responseItems = [];

            $totalCents = 0;
            $totalItems = 0;

            foreach ($cartItems as $item) {

                $quantity = (int) $item['quantity'];

                $unitPriceCents = (int) round(
                    (float) $item['unitPrice'] * 100
                );

                $subtotalCents = $unitPriceCents * $quantity;

                $totalCents += $subtotalCents;
                $totalItems += $quantity;

                $responseItems[] = [
                    'id' => (int) $item['id'],
                    'quantity' => $quantity,
                    'unit_price_cents' => $unitPriceCents,
                    'subtotal_cents' => $subtotalCents
                ];
            }

            sendCartJsonResponse([
                'success' => true,
                'message' => 'Carrinho atualizado com sucesso.',
                'cart_count' => $totalItems,
                'total_items' => $totalItems,
                'total_cents' => $totalCents,
                'items' => $responseItems
            ]);
        }

        if ($action !== 'checkout-cart') {

            $_SESSION['cart_message'] =
            'Carrinho atualizado com sucesso.';

            $_SESSION['cart_success'] = true;
        }

    } catch (Throwable $exception) {

        error_log('Cart error: ' . $exception->getMessage());

        if (
            $isAjaxRequest
            && in_array(
                $action,
                ['add-to-cart', 'update-cart', 'remove-cart'],
                true
            )
        ) {

            sendCartJsonResponse([
                'success' => false,
                'message' => $exception->getMessage()
            ], 400);
        }

        $_SESSION['cart_message'] = $action === 'checkout-cart'
        ? $exception->getMessage()
        : 'Não foi possível atualizar o carrinho.';

        $_SESSION['cart_success'] = false;
    }

    header('Location: /platypus_gestoes/router.php?pagina=carrinho');

    exit;
}


/* 6.3 DIRECT ORDER REGISTRATION */

if (
    $page === 'cardapio'
    && $action === 'order-now'
    && $requestMethod === 'POST'
) {

    $tableToken = trim((string) ($_POST['table_token'] ?? ''));

    try {

        if (!routerCsrfIsValid()) {
            throw new RuntimeException(
                'Sessão expirada. Atualize a página e tente novamente.'
            );
        }

        require_once __DIR__ . '/controller/order.controller.admin.php';

        $result = orderSave();

        $_SESSION['order_message'] = !empty($result['success'])
        ? 'Pedido realizado com sucesso!'
        : ($result['message'] ?? 'Não foi possível realizar o pedido.');

        $_SESSION['order_success'] = (bool) ($result['success'] ?? false);

    } catch (Throwable $exception) {

        error_log('Direct order error: ' . $exception->getMessage());

        $_SESSION['order_message'] = $exception->getMessage();
        $_SESSION['order_success'] = false;
    }

    header(
        'Location: /platypus_gestoes/router.php?pagina=cardapio&mesa='
        . urlencode($tableToken)
    );

    exit;
}


/* 6.4 KITCHEN ORDER STATUS UPDATE */

if (
    $page === 'monitor-cozinha'
    && $action === 'update-order-status'
    && $requestMethod === 'POST'
) {

    require_once __DIR__ . '/controller/order.controller.admin.php';

    $result = updateKitchenOrderStatus();

    $_SESSION['kitchen_message'] =
    $result['message'] ?? 'Operação concluída.';

    $_SESSION['kitchen_success'] =
    (bool) ($result['success'] ?? false);

    header(
        'Location: /platypus_gestoes/router.php?pagina=monitor-cozinha'
    );

    exit;
}


/* 6.5 MARK READY ORDER AS DELIVERED */

if (
    $page === 'pedidos-prontos'
    && $action === 'deliver-order'
    && $requestMethod === 'POST'
) {

    require_once __DIR__ . '/controller/ready.orders.controller.php';

    $result = deliverReadyOrder();

    $_SESSION['ready_orders_message'] =
    $result['message'] ?? 'Operação concluída.';

    $_SESSION['ready_orders_success'] =
    (bool) ($result['success'] ?? false);

    header(
        'Location: /platypus_gestoes/router.php?pagina=pedidos-prontos'
    );

    exit;
}


/* 7. CONFIGURE MENUS BY ROLE */

$menusByRole = [

    'ADMIN' => [
        'gerar-qrcode' => 'Gerar QR-Code',
        'cardapio/admin' => 'Gerenciar Cardápio',
        'ingredientes' => 'Ingredientes e Estoque',
        'mesas' => 'Mesas',
        'relatorios' => 'Relatórios',
        'novo-usuario' => 'Novo Usuário'
    ],

    'KITCHEN' => [
        'monitor-cozinha' => 'Monitor de Pedidos',
        'estoque-rapido' => 'Estoque Rápido'
    ],

    'ATTENDANT' => [
        'pedidos-prontos' => 'Pedidos Prontos',
        'chamados-mesa' => 'Chamados das Mesas',
        'mesas' => 'Mesas'
    ]

];

$menuLinks = [];

if (isAuthenticated()) {

    $currentRole = $_SESSION['user_role'] ?? '';

    if (isset($menusByRole[$currentRole])) {

        foreach ($menusByRole[$currentRole] as $menuRoute => $menuLabel) {

            if (
                !isset($routes[$menuRoute])
                || $routes[$menuRoute]['public']
            ) {
                continue;
            }

            $menuRole = $routes[$menuRoute]['roles'];

            if (
                $menuRole === null
                || (
                    is_array($menuRole)
                    && in_array($currentRole, $menuRole, true)
                )
                || $menuRole === $currentRole
            ) {

                $menuLinks[] = [
                    'label' => $menuLabel,
                    'url' => '/platypus_gestoes/router.php?pagina='
                    . urlencode($menuRoute)
                ];
            }
        }
    }
}


/* 8. LOAD PAGE DATA */

/* Reports */

if ($page === 'relatorios') {
    require_once __DIR__ . '/controller/reports.controller.php';

    $reportsData = reportsLoadData();
}
/* Shopping cart */

if ($page === 'carrinho') {

    require_once __DIR__ . '/controller/cart.controller.php';

    $tableId = (int) ($_SESSION['table_id'] ?? 0);

    $cartItems = $tableId > 0
    ? getTableCart($tableId)
    : [];
}


/* Ready orders */

if ($page === 'pedidos-prontos') {

    require_once __DIR__ . '/controller/ready.orders.controller.php';

    $orders = getReadyOrders();
}


/* Dining tables */

if ($page === 'mesas') {

    require_once __DIR__ . '/controller/table.controller.php';

    $tables = getDiningTables();
}


/* Quick stock */

if ($page === 'estoque-rapido') {

    require_once __DIR__ . '/controller/stock.quick.controller.php';

    try {

        $quickStockData = getQuickStockData();

        $ingredients = $quickStockData['ingredients'] ?? [];
        $products = $quickStockData['products'] ?? [];

        /*
         * The controller returns the movement list as "history".
         * The view expects the variable "$movements".
         */
        $movements = $quickStockData['history'] ?? [];

        /*
         * Adapt the controller's "stock_type" field
         * to the "item_type" field expected by the view.
         */
        foreach ($movements as &$movement) {

            $movement['item_type'] = match ($movement['stock_type'] ?? '') {
                'INGREDIENT' => 'Ingrediente',
                'PRODUCT' => 'Produto',
                default => 'Não identificado'
            };
        }

        unset($movement);

    } catch (Throwable $exception) {

        error_log(
            'Quick stock data loading error: '
            . $exception->getMessage()
        );

        $ingredients = [];
        $products = [];
        $movements = [];

        $_SESSION['quick_stock_message'] =
        'Não foi possível carregar os dados do estoque.';

        $_SESSION['quick_stock_success'] = false;
    }
}


/* 9. LOAD VIEW */

$viewPath = __DIR__ . '/' . $currentRoute['file'];

if (!is_file($viewPath)) {

    http_response_code(500);

    exit(
        'Erro: arquivo da rota não encontrado: '
        . htmlspecialchars(
            $currentRoute['file'],
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

require $viewPath;