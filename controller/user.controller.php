<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function emailExists(string $email): bool
{
    $pdo = getDatabaseConnection();

    $sql = 'SELECT id FROM users WHERE email = :email';

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);

    return (bool) $stmt->fetch();
}

function loginUser(): bool
{
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        return false;
    }

    $pdo = getDatabaseConnection();

    $sql = 'SELECT * FROM users WHERE email = :email';

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (
        !$user
        || !(bool) $user['is_active']
        || !password_verify($password, $user['password_hash'])
    ) {
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['auth'] = true;

    return true;
}

function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $cookieParams = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $cookieParams['path'],
                'domain' => $cookieParams['domain'],
                'secure' => $cookieParams['secure'],
                'httponly' => $cookieParams['httponly'],
                'samesite' => $cookieParams['samesite'] ?? 'Lax'
            ]
        );
    }

    session_destroy();

    header('Location: /platypus_gestoes/router.php?pagina=login');
    exit;
}

function registerUser(): void
{
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $role = (string) ($_POST['role'] ?? 'ATTENDANT');

    if ($name === '' || $email === '' || $password === '') {
        header('Location: /platypus_gestoes/router.php?pagina=novo-usuario&erro=' . urlencode('Por favor, preencha todos os campos obrigatórios.'));
        exit;
    }

    if (mb_strlen($name) > 120) {
        header('Location: /platypus_gestoes/router.php?pagina=novo-usuario&erro=' . urlencode('O nome deve ter até 120 caracteres.'));
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        header('Location: /platypus_gestoes/router.php?pagina=novo-usuario&erro=' . urlencode('Informe um e-mail válido.'));
        exit;
    }

    if (mb_strlen($password) < 8) {
        header('Location: /platypus_gestoes/router.php?pagina=novo-usuario&erro=' . urlencode('A senha deve ter no mínimo 8 caracteres.'));
        exit;
    }

    if (!in_array($role, ['ADMIN', 'KITCHEN', 'ATTENDANT'], true)) {
        header('Location: /platypus_gestoes/router.php?pagina=novo-usuario&erro=' . urlencode('Perfil inválido.'));
        exit;
    }

    try {
        if (emailExists($email)) {
            header('Location: /platypus_gestoes/router.php?pagina=novo-usuario&erro=' . urlencode('Este e-mail já está cadastrado no sistema.'));
            exit;
        }

        $pdo = getDatabaseConnection();

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = 'INSERT INTO users (name, email, password_hash, role)
                VALUES (:name, :email, :password_hash, :role)';

        $stmt = $pdo->prepare($sql);

        $isSaved = $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => $role
        ]);

        if ($isSaved) {
            header('Location: /platypus_gestoes/router.php?pagina=novo-usuario&sucesso=1');
            exit;
        }

        header('Location: /platypus_gestoes/router.php?pagina=novo-usuario&erro=' . urlencode('Erro interno ao salvar no banco de dados.'));
        exit;

    } catch (PDOException $exception) {
        error_log('User registration failed: ' . $exception->getMessage());

        if ($exception->getCode() === '23000') {
            header('Location: /platypus_gestoes/router.php?pagina=novo-usuario&erro=' . urlencode('Este e-mail já está cadastrado no sistema.'));
            exit;
        }

        header('Location: /platypus_gestoes/router.php?pagina=novo-usuario&erro=' . urlencode('Não foi possível cadastrar o usuário.'));
        exit;
    }
}

function isAuthenticated(): bool
{
    return isset($_SESSION['auth']) && $_SESSION['auth'] === true;
}

function hasRole(string $requiredRole): bool
{
    return isset($_SESSION['user_role'])
        && $_SESSION['user_role'] === $requiredRole;
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case 'login':
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (loginUser()) {
                header('Location: /platypus_gestoes/router.php?pagina=dashboard&sucesso=1');
                exit;
            }

            header('Location: /platypus_gestoes/router.php?pagina=login&erro=' . urlencode('E-mail ou senha incorretos.'));
            exit;
        }
        break;

    case 'logout':
        logoutUser();
        break;

    case 'register':
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            registerUser();
        }
        break;
}
