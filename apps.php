<?php
require_once 'config.php';

$auth = new Auth();

error_log("🔍 Apps - Verificando cookie...");

if (!isset($_COOKIE['sso_token'])) {
    error_log("❌ Cookie sso_token não encontrado");
    header('Location: index.php');
    exit;
}

error_log("✅ Cookie encontrado: " . substr($_COOKIE['sso_token'], 0, 10) . "...");

$session = $auth->validateSession($_COOKIE['sso_token']);

error_log("🔍 Resultado validação: " . ($session ? "VÁLIDO" : "INVÁLIDO"));

if (!$session) {
    error_log("❌ Sessão inválida, limpando cookie");
    $auth->clearSessionCookie();
    header('Location: index.php');
    exit;
}

error_log("✅ Usuário logado: " . $session['email']);

$applications = $auth->getUserApplications($session['user_id']);
$firstName = explode(' ', $session['name'])[0];
$isAdmin = ($session['email'] === 'app@importemelhor.com.br');

$pageTitle = 'Ferramentas';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Importe Melhor SSO</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="/public/css/main.css">
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Container -->
        <div class="main-container">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Main Content -->
            <main class="main-content">
                <!-- Page Header -->
                <div class="flex items-center justify-between" style="margin-bottom: 2rem;">
                    <div>
                        <h1 style="margin-bottom: 0.5rem;">Suas Ferramentas</h1>
                        <p style="color: var(--text-secondary); margin: 0;">
                            Acesse todas as ferramentas disponíveis para você
                        </p>
                    </div>
                </div>

                <!-- Applications Grid -->
                <?php if (count($applications) > 0): ?>
                    <div class="app-cards-grid">
                        <?php foreach ($applications as $app): ?>
                        <a href="<?php echo htmlspecialchars($app['app_url']); ?>" class="app-card">
                            <div class="app-icon"><?php echo $app['icon_emoji']; ?></div>
                            <div class="app-name"><?php echo htmlspecialchars($app['app_name']); ?></div>
                            <div class="app-description"><?php echo htmlspecialchars($app['app_description']); ?></div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card" style="text-align: center; padding: 3rem;">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 1rem; color: var(--text-secondary);">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 16v-4M12 8h.01"/>
                        </svg>
                        <h3 style="margin-bottom: 0.5rem;">Nenhuma ferramenta disponível</h3>
                        <p style="color: var(--text-secondary);">Entre em contato com o administrador para obter acesso.</p>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="/public/js/main.js"></script>
</body>
</html>
