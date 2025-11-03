<?php
require_once 'config.php';

$auth = new Auth();

error_log("🔍 Dashboard - Verificando cookie...");

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
$banners = $auth->getActiveBanners();
$firstName = explode(' ', $session['name'])[0];
$isAdmin = ($auth->isAdmin($session['user_id']));

$pageTitle = 'Dashboard';
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
                <!-- Banner Carousel -->
                <?php if (count($banners) > 0): ?>
                <div class="banner-carousel">
                    <?php foreach ($banners as $index => $banner): ?>
                    <div class="banner-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($banner['image_url']); ?>"
                             alt="<?php echo htmlspecialchars($banner['title']); ?>"
                             class="banner-image">
                        <div class="banner-content">
                            <h2 class="banner-title"><?php echo htmlspecialchars($banner['title']); ?></h2>
                            <?php if ($banner['description']): ?>
                            <p class="banner-description"><?php echo htmlspecialchars($banner['description']); ?></p>
                            <?php endif; ?>
                            <?php if ($banner['link_url'] && $banner['link_text']): ?>
                            <a href="<?php echo htmlspecialchars($banner['link_url']); ?>" class="btn btn-accent" style="margin-top: 1rem;">
                                <?php echo htmlspecialchars($banner['link_text']); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Controls -->
                    <?php if (count($banners) > 1): ?>
                    <div class="carousel-controls">
                        <button class="carousel-btn carousel-btn-prev" aria-label="Anterior">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15 18l-6-6 6-6"/>
                            </svg>
                        </button>
                        <button class="carousel-btn carousel-btn-next" aria-label="Próximo">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Indicators -->
                    <div class="carousel-indicators"></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Welcome Section -->
                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <div>
                            <h1 style="margin-bottom: 0.5rem;">Olá, <?php echo htmlspecialchars($firstName); ?>! 👋</h1>
                            <p style="color: var(--color-gray-500); margin: 0;">
                                Bem-vindo ao painel central da Importe Melhor
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 0.875rem; color: var(--color-gray-500);">
                                <?php echo date('d/m/Y'); ?>
                            </div>
                            <div style="font-size: 1.5rem; font-weight: 600; color: var(--color-primary);">
                                <?php echo date('H:i'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-4" style="margin-bottom: 2rem;">
                    <div class="stats-card">
                        <div class="stats-header">
                            <div class="stats-icon primary">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="7" height="7"/>
                                    <rect x="14" y="3" width="7" height="7"/>
                                    <rect x="14" y="14" width="7" height="7"/>
                                    <rect x="3" y="14" width="7" height="7"/>
                                </svg>
                            </div>
                        </div>
                        <div class="stats-value"><?php echo count($applications); ?></div>
                        <div class="stats-label">Ferramentas Disponíveis</div>
                    </div>

                    <div class="stats-card">
                        <div class="stats-header">
                            <div class="stats-icon success">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                                </svg>
                            </div>
                        </div>
                        <div class="stats-value">100%</div>
                        <div class="stats-label">Sistema Ativo</div>
                    </div>

                    <div class="stats-card">
                        <div class="stats-header">
                            <div class="stats-icon accent">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </div>
                        </div>
                        <div class="stats-value"><?php echo $isAdmin ? 'Admin' : 'User'; ?></div>
                        <div class="stats-label">Tipo de Conta</div>
                    </div>

                    <div class="stats-card">
                        <div class="stats-header">
                            <div class="stats-icon warning">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2v20M2 12h20"/>
                                </svg>
                            </div>
                        </div>
                        <div class="stats-value"><?php echo count($banners); ?></div>
                        <div class="stats-label">Destaques Ativos</div>
                    </div>
                </div>

                <!-- Applications Section -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Suas Ferramentas</h2>
                        <a href="?page=apps" class="btn btn-outline">
                            Ver Todas
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>

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
                        <div style="text-align: center; padding: 3rem; color: var(--color-gray-500);">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 1rem;">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 16v-4M12 8h.01"/>
                            </svg>
                            <h3 style="margin-bottom: 0.5rem;">Nenhuma ferramenta disponível</h3>
                            <p>Entre em contato com o administrador para obter acesso.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="/public/js/main.js"></script>
    <script src="/public/js/carousel.js"></script>
<?php include 'includes/chat_widget.php'; ?>
</body>
</html>
