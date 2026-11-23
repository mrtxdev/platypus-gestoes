
<?php

require_once __DIR__ . '/../../../controller/generate.qrcode.controller.php';

loadEnv(__DIR__ . '/../../../.env');

if (empty($_SESSION['csrf_qr'])) {
    $_SESSION['csrf_qr'] = bin2hex(random_bytes(32));
}

$tables = getQrTables();

$applicationBaseUrl = trim(
    (string) (
        getenv('APP_BASE_URL')
        ?: ($_ENV['APP_BASE_URL'] ?? '')
    )
);

if ($applicationBaseUrl === '') {
    $scheme = (
        !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off'
    ) ? 'https' : 'http';

    $host = preg_replace(
        '/[^a-zA-Z0-9.\-:\[\]]/',
        '',
        (string) ($_SERVER['HTTP_HOST'] ?? 'localhost')
    );

    $applicationBaseUrl = $scheme
        . '://'
        . ($host !== '' ? $host : 'localhost')
        . '/platypus_gestoes';
}

$applicationBaseUrl = rtrim($applicationBaseUrl, '/');

$baseUrl = $applicationBaseUrl
    . '/router.php?pagina=cardapio&mesa=';

$baseHost = strtolower(
    (string) parse_url($applicationBaseUrl, PHP_URL_HOST)
);

$isLocalHost = in_array(
    $baseHost,
    ['localhost', '127.0.0.1', '::1'],
    true
);

$statusLabels = [
    'AVAILABLE' => 'Disponível',
    'OCCUPIED' => 'Ocupada',
    'INACTIVE' => 'Inativa'
];

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Platypus - QR-Code das Mesas</title>

    <link rel="stylesheet" href="/platypus_gestoes/view/public/assets/css/style.css?v=12">
    <link rel="shortcut icon" href="/platypus_gestoes/view/public/assets/images/icons/favicon.ico" type="image/x-icon">

</head>

<body>

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main>
        <div class="content-header">
            <h2>QR-Code das Mesas</h2>
            <p>
                Cadastre as mesas e gere códigos para os clientes acessarem
                o cardápio digital pelo celular.
            </p>
        </div>

        <?php if ($isLocalHost): ?>
            <div class="alert alert-info">
                O endereço atual utiliza localhost. Configure APP_BASE_URL
                no arquivo .env com o IP da máquina ou domínio do sistema
                para permitir o acesso pelo celular.
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['erro'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars((string) $_GET['erro'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['sucesso'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars((string) $_GET['sucesso'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <section class="card">
            <h3>Cadastrar nova mesa</h3>

            <form
                class="qr-toolbar"
                action="/platypus_gestoes/router.php?pagina=gerar-qrcode&action=generate-qr-code"
                method="POST"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_qr'], ENT_QUOTES, 'UTF-8') ?>"
                >

                <div class="form-group">
                    <label for="table_number">Número ou identificação da mesa</label>

                    <input
                        type="text"
                        id="table_number"
                        name="table_number"
                        maxlength="30"
                        placeholder="Ex.: 06 ou Varanda 1"
                        required
                    >
                </div>

                <button type="submit" class="btn">
                    Cadastrar mesa e gerar QR-Code
                </button>
            </form>
        </section>

        <div class="content-header section-heading-spaced">
            <h3>Mesas cadastradas (<?= count($tables) ?>)</h3>
            <p>
                Cada QR-Code possui um link exclusivo para identificar sua mesa.
            </p>
        </div>

        <?php if (empty($tables)): ?>
            <section class="card">
                <p>Nenhuma mesa cadastrada.</p>
                <p>Use o formulário acima para adicionar a primeira mesa.</p>
            </section>
        <?php else: ?>
            <div class="qr-grid">
                <?php foreach ($tables as $table): ?>
                    <?php
                    $url = $baseUrl . rawurlencode($table['qr_token']);

                    $statusLabel = $statusLabels[$table['status']]
                        ?? $table['status'];

                    $escapedUrl = htmlspecialchars(
                        $url,
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    $escapedTableNumber = htmlspecialchars(
                        $table['table_number'],
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>

                    <article class="card qr-card">
                        <div class="qr-card-top">
                            <h3 class="qr-card-title">
                                Mesa <?= $escapedTableNumber ?>
                            </h3>

                            <span class="status-mesa">
                                <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                        <div
                            class="qr-image"
                            data-qr="<?= $escapedUrl ?>"
                        >
                            <span>Gerando QR-Code...</span>
                        </div>

                        <div class="qr-url">
                            <label for="table-url-<?= (int) $table['id'] ?>">
                                Link desta mesa
                            </label>

                            <input
                                id="table-url-<?= (int) $table['id'] ?>"
                                type="text"
                                readonly
                                value="<?= $escapedUrl ?>"
                            >
                        </div>

                        <div class="qr-card-actions">
                            <button type="button" class="btn" data-print-qr>
                                Imprimir
                            </button>

                            <button
                                type="button"
                                class="btn btn-secondary"
                                data-copy-qr="<?= $escapedUrl ?>"
                            >
                                Copiar link
                            </button>

                            <form
                                action="/platypus_gestoes/router.php?pagina=gerar-qrcode&action=delete-table"
                                method="POST"
                                data-confirm-delete="Tem certeza de que deseja excluir esta mesa?"
                            >
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $table['id'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars($_SESSION['csrf_qr'], ENT_QUOTES, 'UTF-8') ?>"
                                >

                                <button type="submit" class="btn btn-secondary">
                                    Excluir
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="/platypus_gestoes/view/public/assets/js/qrcode.js?v=3"></script>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>

</body>
</html>
