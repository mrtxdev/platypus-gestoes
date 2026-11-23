<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Platypus - Cadastro de usuário</title>
    <link rel="stylesheet" href="/platypus_gestoes/view/public/assets/css/style.css?v=12">
    <link rel="shortcut icon" href="/platypus_gestoes/view/public/assets/images/icons/favicon.ico" type="image/x-icon">
</head>
<body>
    <main class="standalone-registration">
        <section class="card">
            <h1>Criar usuário</h1>
            <p>Cadastre um acesso para a equipe do restaurante.</p>

            <form action="/platypus_gestoes/router.php?pagina=novo-usuario" method="POST">
                <input type="hidden" name="action" value="register">

                <div class="form-group">
                    <label for="name">Nome completo</label>
                    <input type="text" id="name" name="name" maxlength="120" autocomplete="name" required>
                </div>

                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" maxlength="190" autocomplete="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>
                </div>

                <button type="submit" class="btn">Cadastrar usuário</button>
            </form>
        </section>
    </main>
</body>
</html>
