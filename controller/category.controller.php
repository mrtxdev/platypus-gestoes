
<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/category.model.php';

function add(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));

    if ($name === '') {
        header('Location: /platypus_gestoes/router.php?pagina=categorias/adicionar&erro=nome_obrigatorio');
        exit;
    }

    try {
        $database = getDatabaseConnection();

        $statement = $database->prepare(
            'INSERT INTO categories (
                name,
                description,
                is_active
            ) VALUES (
                :name,
                :description,
                :isActive
            )'
        );

        $statement->execute([
            ':name' => $name,
            ':description' => $description !== '' ? $description : null,
            ':isActive' => 1
        ]);

        header('Location: /platypus_gestoes/router.php?pagina=cardapio/admin&sucesso=categoria_adicionada');
        exit;

    } catch (Throwable $exception) {
        error_log('Category registration failed: ' . $exception->getMessage());

        header('Location: /platypus_gestoes/router.php?pagina=categorias/adicionar&erro=erro_ao_cadastrar');
        exit;
    }
}

function all(): array
{
    try {
        $database = getDatabaseConnection();

        $statement = $database->prepare(
            'SELECT
                id,
                name,
                description,
                is_active AS isActive
             FROM categories
             WHERE is_active = :isActive
             ORDER BY name ASC'
        );

        $statement->execute([
            ':isActive' => 1
        ]);

        return $statement->fetchAll(PDO::FETCH_CLASS, Category::class);

    } catch (Throwable $exception) {
        error_log('Category retrieval failed: ' . $exception->getMessage());

        return [];
    }
}
