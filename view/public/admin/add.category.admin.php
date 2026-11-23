
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Platypus - Adicionar Categoria</title>

    <link rel="stylesheet" href="/platypus_gestoes/view/public/assets/css/style.css?v=12">
    <link rel="shortcut icon" href="/platypus_gestoes/view/public/assets/images/icons/favicon.ico" type="image/x-icon">
</head>

<body>

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main>
        <div class="content-header">
            <h2>Adicionar Categoria</h2>
            <p>Cadastre uma nova categoria para organizar os produtos do cardápio.</p>
        </div>

        <?php
        $errorMessages = [
            'nome_obrigatorio' => 'Informe o nome da categoria.',
            'erro_ao_cadastrar' => 'Não foi possível cadastrar a categoria. Tente novamente.'
        ];

        $error = (string) ($_GET['erro'] ?? '');
        ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars(
                    $errorMessages[$error] ?? 'Ocorreu um erro ao cadastrar a categoria.',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['sucesso'])): ?>
            <div class="alert alert-success">
                Categoria cadastrada com sucesso!
            </div>
        <?php endif; ?>

        <section class="card">
            <form
                action="/platypus_gestoes/router.php?pagina=categorias/adicionar&action=register-category"
                method="POST"
                accept-charset="UTF-8"
            >
                <div class="form-group">
                    <label for="name">Nome da categoria *</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="100"
                        placeholder="Ex.: Hambúrgueres"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="description">Descrição</label>

                    <textarea
                        id="description"
                        name="description"
                        maxlength="255"
                        rows="4"
                        placeholder="Descreva os produtos desta categoria"
                    ></textarea>
                </div>

                <div class="form-group">
                    <label for="is_active">Status da categoria</label>

                    <select id="is_active" name="is_active" required>
                        <option value="1" selected>Ativa</option>
                        <option value="0">Inativa</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">
                        Cadastrar Categoria
                    </button>

                    <a
                        href="/platypus_gestoes/router.php?pagina=cardapio/admin"
                        class="btn btn-secondary"
                    >
                        Cancelar
                    </a>
                </div>
            </form>
        </section>
    </main>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>

</body>
</html>
