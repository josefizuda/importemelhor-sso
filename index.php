<?php
require_once 'config.php';

$auth = new Auth();

// Verificar se já está logado via cookie SSO
if (isset($_COOKIE['sso_token'])) {
    $session = $auth->validateSession($_COOKIE['sso_token']);
    if ($session) {
        header('Location: /dashboard.php');
        exit();
    }
}

$error = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IM Connect</title>
    <link rel="stylesheet" href="/public/css/login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="login-background" id="loginBackground">
    <div class="astronaut-container" id="astronautContainer"></div>
</div>

<div class="login-container" id="loginContainer">
    <div class="glass-card" id="glassCard">
        <div class="logo-header">
            <img src="https://conteudo.importemelhor.com.br/wp-content/uploads/2025/12/logoIMConnect-V2.webp" alt="IM Connect" class="logo-img" style="max-width: 200px;">
        </div>

        <div class="login-header">
            <h2>Bem-vindo de volta! 👋</h2>
            <p>Acesse todas as ferramentas com login único</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M12 9V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <a href="/sso-login.php" class="btn-microsoft" id="btnMicrosoft">
            <svg width="21" height="21" viewBox="0 0 21 21" fill="none">
                <rect width="10" height="10" fill="#F25022"/>
                <rect x="11" width="10" height="10" fill="#7FBA00"/>
                <rect y="11" width="10" height="10" fill="#00A4EF"/>
                <rect x="11" y="11" width="10" height="10" fill="#FFB900"/>
            </svg>
            <span>Entrar com Microsoft</span>
        </a>

        <div class="support-link">
            <span>Problemas para acessar?</span>
            <a href="mailto:suporte@importemelhor.com.br">Fale com o suporte</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnMicrosoft = document.getElementById('btnMicrosoft');
    const glassCard = document.getElementById('glassCard');
    const astronautContainer = document.getElementById('astronautContainer');

    btnMicrosoft.addEventListener('click', function(e) {
        e.preventDefault();

        // Add centering animation class
        document.body.classList.add('astronaut-centering');
        glassCard.classList.add('card-fade-out');
        astronautContainer.classList.add('astronaut-center');

        // Wait for animation then redirect
        setTimeout(function() {
            window.location.href = btnMicrosoft.href;
        }, 800);
    });
});
</script>

</body>
</html>
