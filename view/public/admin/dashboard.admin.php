<?php

/*
|--------------------------------------------------------------------------
| USER INFORMATION
|--------------------------------------------------------------------------
*/

$userRole = strtoupper(
    trim((string) ($_SESSION['user_role'] ?? ''))
);

$validRoles = [
    'ADMIN',
    'KITCHEN',
    'ATTENDANT'
];

$isValidRole = in_array($userRole, $validRoles, true);

$userName = trim(
    (string) (
        $_SESSION['user_name']
        ?? $_SESSION['name']
        ?? $_SESSION['username']
        ?? ''
    )
);

$firstName = '';

if ($userName !== '') {
    $nameParts = preg_split('/\s+/', $userName);
    $firstName = $nameParts[0] ?? '';
}

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

$escape = static function ($value): string {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
};

/*
|--------------------------------------------------------------------------
| GREETING
|--------------------------------------------------------------------------
*/

$currentHour = (int) date('H');

$greeting = match (true) {
    $currentHour >= 5 && $currentHour < 12 => 'Bom dia',
    $currentHour >= 12 && $currentHour < 18 => 'Boa tarde',
    default => 'Boa noite'
};

$welcomeTitle = $firstName !== ''
    ? $greeting . ', ' . $firstName . '!'
    : 'Bem-vindo ao Platypus!';

/*
|--------------------------------------------------------------------------
| DASHBOARD CONFIGURATION
|--------------------------------------------------------------------------
*/

$dashboardConfig = [

    'ADMIN' => [
        'title' => 'Painel Administrativo',

        'description' => 'Gerencie o restaurante, acompanhe as operações e mantenha tudo funcionando.'
    ],

    'KITCHEN' => [
        'title' => 'Painel da Cozinha',

        'description' => 'Acesse os pedidos e mantenha a operação da cozinha organizada.'
    ],

    'ATTENDANT' => [
        'title' => 'Painel de Atendimento',

        'description' => 'Acompanhe os pedidos e cuide das solicitações dos clientes.'
    ]

];

$dashboardTitle = $dashboardConfig[$userRole]['title']
    ?? 'Painel Principal';

$dashboardDescription = $dashboardConfig[$userRole]['description']
    ?? 'Acesse as funcionalidades disponíveis no sistema.';

/*
|--------------------------------------------------------------------------
| QUICK ACTIONS
|--------------------------------------------------------------------------
*/

$quickActions = [];

if ($userRole === 'ADMIN') {

    $quickActions = [

        [
            'number' => '01',
            'title' => 'Gerenciar cardápio',
            'description' => 'Produtos e categorias',
            'url' => 'cardapio/admin'
        ],

        [
            'number' => '02',
            'title' => 'Gerenciar QR Codes',
            'description' => 'Identificação das mesas',
            'url' => 'gerar-qrcode'
        ],

        [
            'number' => '03',
            'title' => 'Mesas ativas',
            'description' => 'Acompanhar mesas ocupadas',
            'url' => 'mesas-ativas'
        ],

        [
            'number' => '04',
            'title' => 'Relatórios',
            'description' => 'Desempenho financeiro e operacional',
            'url' => 'relatorios'
        ],

        [
            'number' => '05',
            'title' => 'Cadastrar funcionário',
            'description' => 'Gerenciar usuários do sistema',
            'url' => 'novo-usuario'
        ]

    ];

} elseif ($userRole === 'KITCHEN') {

    $quickActions = [

        [
            'number' => '01',
            'title' => 'Monitor da cozinha',
            'description' => 'Acompanhar pedidos',
            'url' => 'monitor-cozinha'
        ],

        [
            'number' => '02',
            'title' => 'Estoque rápido',
            'description' => 'Ingredientes e quantidades',
            'url' => 'estoque-rapido'
        ]

    ];

} elseif ($userRole === 'ATTENDANT') {

    $quickActions = [

        [
            'number' => '01',
            'title' => 'Pedidos prontos',
            'description' => 'Consultar pedidos para entrega',
            'url' => 'pedidos-prontos'
        ],

        [
            'number' => '02',
            'title' => 'Chamados de mesa',
            'description' => 'Atender solicitações dos clientes',
            'url' => 'chamados-mesa'
        ],

        [
            'number' => '03',
            'title' => 'Mesas ativas',
            'description' => 'Acompanhar mesas ocupadas',
            'url' => 'mesas-ativas'
        ]

    ];

}

/*
|--------------------------------------------------------------------------
| ROLE CLASS
|--------------------------------------------------------------------------
*/

$dashboardRoleClass = strtolower($userRole);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Platypus - <?= $escape($dashboardTitle) ?>
    </title>

    <link
        rel="stylesheet"
        href="/platypus_gestoes/view/public/assets/css/style.css?v=12"
    >

    <link
        rel="shortcut icon"
        href="/platypus_gestoes/view/public/assets/images/icons/favicon.ico"
        type="image/x-icon"
    >

</head>

<body>

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="dashboard dashboard--<?= $escape($dashboardRoleClass) ?>">

        <!-- WELCOME -->

        <section class="dashboard-header">

            <div>

                <span class="dashboard-label">
                    PLATYPUS
                </span>

                <h1>
                    <?= $escape($welcomeTitle) ?>
                </h1>

                <p>
                    <?= $escape($dashboardDescription) ?>
                </p>

                <p>
                    <?= $escape(date('d/m/Y')) ?>
                </p>

            </div>

        </section>

        <?php if ($isValidRole): ?>

            <!-- QUICK ACTIONS -->

            <?php if (!empty($quickActions)): ?>

                <section class="dashboard-section">

                    <div class="dashboard-section-header">

                        <div>

                            <h2>
                                Acesso rápido
                            </h2>

                            <p>
                                Escolha uma funcionalidade para começar.
                            </p>

                        </div>

                    </div>

                    <div class="dashboard-actions">

                        <?php foreach ($quickActions as $action): ?>

                            <a
                                href="/platypus_gestoes/router.php?pagina=<?= urlencode($action['url']) ?>"
                                class="dashboard-action"
                            >

                                <div
                                    class="action-icon"
                                    aria-hidden="true"
                                >
                                    <?= $escape($action['number']) ?>
                                </div>

                                <div>

                                    <strong>
                                        <?= $escape($action['title']) ?>
                                    </strong>

                                    <span>
                                        <?= $escape($action['description']) ?>
                                    </span>

                                </div>

                                <span
                                    class="action-arrow"
                                    aria-hidden="true"
                                >
                                    →
                                </span>

                            </a>

                        <?php endforeach; ?>

                    </div>

                </section>

            <?php endif; ?>

        <?php else: ?>

            <!-- INVALID ROLE -->

            <section class="dashboard-panel">

                <div class="dashboard-panel-content">

                    <div class="dashboard-empty">

                        <strong>
                            Cargo não reconhecido
                        </strong>

                        <span>
                            Não foi possível identificar as permissões deste usuário.
                            Entre em contato com um administrador.
                        </span>

                    </div>

                </div>

            </section>

        <?php endif; ?>

    </main>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>

</body>

</html>
