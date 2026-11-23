<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../controller/user.controller.php';

$isLogged = isAuthenticated();

$userName = $_SESSION['user_name'] ?? '';

$menuLinks = isset($menuLinks) && is_array($menuLinks)
    ? $menuLinks
    : [];

?>

<header id="header">
    <div class="header-container">

        <div class="header-brand">
            <a href="/platypus_gestoes/router.php?pagina=dashboard">
                platypus gestões
            </a>
        </div>

        <nav class="header-nav">

            <?php if ($isLogged): ?>

                <?php foreach ($menuLinks as $link): ?>

                    <?php
                    if (
                        !is_array($link) ||
                        !isset($link['url'], $link['label'])
                    ) {
                        continue;
                    }
                    ?>

                    <a
                        href="<?= htmlspecialchars(
                            (string) $link['url'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        <?= htmlspecialchars(
                            (string) $link['label'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </a>

                <?php endforeach; ?>

                <span class="header-user">
                    Olá,
                    <strong>
                        <?= htmlspecialchars(
                            (string) $userName,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>!
                    </strong>
                </span>

                <a href="/platypus_gestoes/router.php?action=logout">
                    Sair
                </a>

            <?php endif; ?>

        </nav>

    </div>
</header>
