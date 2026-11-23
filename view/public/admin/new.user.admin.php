
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Platypus - Gestão de Restaurante</title>

    <link rel="stylesheet" href="/platypus_gestoes/view/public/assets/css/style.css?v=12">
    <link rel="shortcut icon" href="/platypus_gestoes/view/public/assets/images/icons/favicon.ico" type="image/x-icon">
</head>
<body>

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main>
        <div class="container">

            <div class="sidebar">
                <div>
                    <h2>Gestão de Equipe</h2>
                    <p>
                        Cadastre e gerencie os perfis de acesso do seu
                        restaurante para manter a operação organizada.
                    </p>
                </div>

                <div class="info-card">
                    <p class="title">Níveis de Acesso:</p>
                    <p>• <strong>Atendente:</strong> Salão, comandas e pedidos.</p>
                    <p>• <strong>Cozinha:</strong> Monitor de preparos e estoque rápido.</p>
                    <p>• <strong>Admin:</strong> Controle total, relatórios e caixa.</p>
                </div>
            </div>

            <div class="content">
                <div class="content-header">
                    <h3>Criar Novo Usuário</h3>
                    <p>Insira as credenciais do funcionário abaixo</p>
                </div>

                <?php if (!empty($_GET['erro'])): ?>
                    <div class="alert alert-error">
                        <span>⚠️</span>
                        <p>
                            <?= htmlspecialchars(
                                (string) $_GET['erro'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_GET['sucesso'])): ?>
                    <div class="alert alert-success">
                        <span>✅</span>
                        <p>Usuário cadastrado com sucesso na plataforma!</p>
                    </div>
                <?php endif; ?>

                <form
                    action="/platypus_gestoes/router.php?pagina=novo-usuario&action=register"
                    method="POST"
                >
                    <div class="form-group">
                        <label for="name">Nome Completo</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            placeholder="Ex: João Silva"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail Corporativo</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="nome@platypus.com"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Senha de Acesso</label>

                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Mínimo 8 caracteres"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                id="togglePassword"
                                aria-label="Mostrar ou ocultar senha"
                            >👁️</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="role">Função / Perfil</label>

                        <select id="role" name="role" required>
                            <option value="ATTENDANT" selected>
                                🍽️ Atendente / Garçom
                            </option>

                            <option value="KITCHEN">
                                🍳 Equipe da Cozinha
                            </option>

                            <option value="ADMIN">
                                💼 Administrador do Sistema
                            </option>
                        </select>
                    </div>

                    <button type="submit">Cadastrar Colaborador</button>
                </form>
            </div>

        </div>
    </main>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>

    <script src="/platypus_gestoes/view/public/assets/js/eye.js"></script>

</body>
</html>
