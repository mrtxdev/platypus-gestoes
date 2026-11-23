
<?php

$tables = $tables ?? [];
$csrfToken = $_SESSION['csrf_token'] ?? '';

$message = $_SESSION['table_message'] ?? '';
$success = $_SESSION['table_success'] ?? false;

unset(
    $_SESSION['table_message'],
    $_SESSION['table_success']
);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Platypus - Mesas</title>

    <link rel="stylesheet" href="/platypus_gestoes/view/public/assets/css/style.css?v=12">
</head>

<body>

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="table-management">

        <section class="page-header">
            <h1>Gerenciamento de mesas</h1>
            <p>Acompanhe o estado das mesas do restaurante.</p>
        </section>

        <?php if ($message !== ''): ?>
            <div class="<?= $success ? 'alert-success' : 'alert-error' ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <section class="table-grid">

            <?php if (empty($tables)): ?>

                <p>Nenhuma mesa cadastrada.</p>

            <?php else: ?>

                <?php foreach ($tables as $table): ?>

                    <?php
                    $status = $table['status'];

                    $statusLabels = [
                        'AVAILABLE' => 'Disponível',
                        'OCCUPIED' => 'Ocupada',
                        'INACTIVE' => 'Inativa'
                    ];

                    $statusLabel = $statusLabels[$status] ?? 'Desconhecido';
                    $statusClass = strtolower($status);
                    ?>

                    <article class="table-card">

                        <div class="table-card-header">

                            <h2>
                                Mesa <?= htmlspecialchars(
                                    (string) $table['table_number'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </h2>

                            <span class="table-status table-status-<?= htmlspecialchars(
                                $statusClass,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">
                                <?= htmlspecialchars(
                                    $statusLabel,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                        </div>

                        <div class="table-card-actions">

                            <?php if ($status === 'OCCUPIED'): ?>

                                <form
                                    method="POST"
                                    action="/platypus_gestoes/router.php?pagina=mesas"
                                    onsubmit="return confirm('Tem certeza de que deseja liberar esta mesa?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="table_id"
                                        value="<?= (int) $table['id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="release-table"
                                    >

                                    <button type="submit">
                                        Liberar mesa
                                    </button>

                                </form>

                            <?php elseif ($status === 'AVAILABLE'): ?>

                                <span class="table-action-info">
                                    Aguardando cliente
                                </span>

                            <?php else: ?>

                                <span class="table-action-info">
                                    Mesa indisponível
                                </span>

                            <?php endif; ?>

                            <?php if ($status !== 'OCCUPIED'): ?>

                                <form
                                    method="POST"
                                    action="/platypus_gestoes/router.php?pagina=mesas"
                                    onsubmit="return confirm('Tem certeza de que deseja remover esta mesa? Essa ação não poderá ser desfeita.');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="table_id"
                                        value="<?= (int) $table['id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="remove-table"
                                    >

                                    <button
                                        type="submit"
                                        class="table-remove-button"
                                    >
                                        Remover mesa
                                    </button>

                                </form>

                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>

    </main>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>

</body>
</html>
