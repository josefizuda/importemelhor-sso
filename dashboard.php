<?php
require_once 'config.php';

$auth = new Auth();

if (!isset($_COOKIE['sso_token'])) {
    header('Location: index.php');
    exit;
}

$session = $auth->validateSession($_COOKIE['sso_token']);
if (!$session) {
    $auth->clearSessionCookie();
    header('Location: index.php');
    exit;
}

$applications = $auth->getUserApplications($session['user_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Importe Melhor</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-left">
            <span class="logo">🚢</span>
            <span class="company-name">Importe Melhor</span>
        </div>
        <div class="user-info">
            <div class="user-avatar"><?php echo strtoupper(substr($session['name'], 0, 1)); ?></div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($session['name']); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($session['email']); ?></div>
            </div>
            <a href="logout.php" class="logout-btn">Sair</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="welcome-card">
            <h1>👋 Olá, <?php echo htmlspecialchars(explode(' ', $session['name'])[0]); ?>!</h1>
            <p>Bem-vindo ao portal da Importe Melhor</p>
        </div>
        
        <h2>Suas Ferramentas</h2>
        
        <?php if (count($applications) > 0): ?>
            <div class="apps-grid">
                <?php foreach ($applications as $app): ?>
                    <a href="<?php echo htmlspecialchars($app['app_url']); ?>" class="app-card">
                        <span class="app-icon"><?php echo $app['icon_emoji']; ?></span>
                        <h3><?php echo htmlspecialchars($app['app_name']); ?></h3>
                        <p><?php echo htmlspecialchars($app['app_description']); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>Nenhuma ferramenta disponível</h3>
                <p>Entre em contato com o administrador para obter acesso.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
