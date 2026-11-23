
<?php

require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function redirectQr(string $message, bool $success = false): void
{
    $parameter = $success ? 'sucesso' : 'erro';

    header(
        'Location: /platypus_gestoes/router.php?pagina=gerar-qrcode&'
        . $parameter . '=' . urlencode($message)
    );

    exit;
}

function validateQrCsrf(): bool
{
    $sessionToken = (string) ($_SESSION['csrf_qr'] ?? '');
    $formToken = (string) ($_POST['csrf_token'] ?? '');

    return $sessionToken !== ''
        && $formToken !== ''
        && hash_equals($sessionToken, $formToken);
}

function generateQrCode(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        redirectQr('Requisição inválida.');
    }

    if (!validateQrCsrf()) {
        redirectQr('Sua sessão expirou. Atualize a página e tente novamente.');
    }

    $tableNumber = trim((string) ($_POST['table_number'] ?? ''));

    if ($tableNumber === '' || mb_strlen($tableNumber) > 30) {
        redirectQr('Informe uma identificação válida para a mesa.');
    }

    try {
        $database = getDatabaseConnection();

        $statement = $database->prepare(
            'INSERT INTO dining_tables (
                table_number,
                qr_token,
                status
            ) VALUES (
                :tableNumber,
                :qrToken,
                :status
            )'
        );

        $statement->execute([
            ':tableNumber' => $tableNumber,
            ':qrToken' => bin2hex(random_bytes(18)),
            ':status' => 'AVAILABLE'
        ]);

        redirectQr('Mesa cadastrada e QR-Code criado com sucesso!', true);

    } catch (PDOException $exception) {
        error_log('QR table registration failed: ' . $exception->getMessage());

        if ((string) $exception->getCode() === '23000') {
            redirectQr('Já existe uma mesa com essa identificação.');
        }

        redirectQr('Não foi possível cadastrar a mesa. Verifique o banco de dados.');

    } catch (Throwable $exception) {
        error_log('QR code generation failed: ' . $exception->getMessage());

        redirectQr('Não foi possível gerar o QR-Code.');
    }
}

function deleteQrTable(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        redirectQr('Requisição inválida.');
    }

    if (!validateQrCsrf()) {
        redirectQr('Sua sessão expirou. Atualize a página e tente novamente.');
    }

    $id = filter_var(
        $_POST['id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id === false) {
        redirectQr('Identificação da mesa inválida.');
    }

    try {
        $database = getDatabaseConnection();

        $statement = $database->prepare(
            'DELETE FROM dining_tables
             WHERE id = :id'
        );

        $statement->execute([
            ':id' => (int) $id
        ]);

        if ($statement->rowCount() === 0) {
            redirectQr('Mesa não encontrada.');
        }

        redirectQr('Mesa excluída com sucesso!', true);

    } catch (Throwable $exception) {
        error_log('QR table deletion failed: ' . $exception->getMessage());

        redirectQr(
            'Não foi possível excluir esta mesa. Ela pode estar vinculada a pedidos.'
        );
    }
}

function getQrTables(): array {
    $database = getDatabaseConnection();

    $statement = $database->prepare(
        'SELECT
            id,
            table_number,
            qr_token,
            status,
            created_at
         FROM dining_tables
         ORDER BY table_number ASC'
    );

    $statement->execute();

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}
