
<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Platypus - Login Administrativo</title>

    <link rel="stylesheet" href="/platypus_gestoes/view/public/assets/css/style.css?v=12">
    <link rel="shortcut icon" href="/platypus_gestoes/view/public/assets/images/icons/favicon.ico" type="image/x-icon">
</head>

<body>

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main>
        <div class="container">

            <aside class="sidebar">
                <div>
                    <h2>Acesso ao Sistema</h2>
                    <p>
                        Entre com suas credenciais corporativas para
                        gerenciar a operação e acompanhar o fluxo
                        do seu restaurante.
                    </p>
                </div>

                <div class="info-card">
                    <p class="title">Aviso de Segurança:</p>
                    <p>Mantenha seus dados de login em sigilo.</p>
                </div>
            </aside>

            <section class="content">
                <div class="content-header">
                    <h3>Identifique-se</h3>
                    <p>Insira seu e-mail e senha cadastrados</p>
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

                <form
                    action="/platypus_gestoes/router.php?pagina=login&action=login"
                    method="POST"
                >
                    <div class="form-group">
                        <label for="email">E-mail Corporativo</label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="nome@platypus.com"
                            autocomplete="username"
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
                                placeholder="Digite sua senha"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                id="togglePassword"
                                aria-label="Mostrar ou ocultar senha"
                            >👁️</button>
                        </div>
                    </div>

                    <button type="submit">Entrar no Painel</button>
                </form>
            </section>

        </div>
    </main>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>

    <script src="/platypus_gestoes/view/public/assets/js/eye.js"></script>

</body>
</html>
