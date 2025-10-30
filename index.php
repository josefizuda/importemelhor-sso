<?php
require_once 'config.php';

$auth = new Auth();

// Verifica se já está logado
if (isset($_COOKIE['sso_token'])) {
    $session = $auth->validateSession($_COOKIE['sso_token']);
    if ($session) {
        header('Location: dashboard.php');
        exit;
    }
}

$return_url = $_GET['return_url'] ?? 'dashboard.php';
$_SESSION['return_url'] = $return_url;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Importe Melhor</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">🚢</div>
        <div class="company-name">Importe Melhor</div>
        <h1>Bem-vindo</h1>
        <p>Acesse todas as ferramentas com login único</p>
        
        <a href="login.php" class="ms-button">
            <svg class="ms-icon" viewBox="0 0 21 21">
                <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
            </svg>
            Entrar com Microsoft
        </a>
        
        <div class="features">
            <div class="feature">✓ Login único para todas plataformas</div>
            <div class="feature">✓ Segurança corporativa Microsoft</div>
            <div class="feature">✓ Acesso rápido e simplificado</div>
        </div>
    </div>
</body>
</html>
