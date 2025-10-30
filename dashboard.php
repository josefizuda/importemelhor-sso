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
$firstName = explode(' ', $session['name'])[0];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Central de Apps - Importe Melhor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0423b2 0%, #021a75 100%);
            min-height: 100vh;
            color: white;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 280px;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            padding: 2rem 0;
        }

        .sidebar-header {
            padding: 0 2rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .logo-icon {
            font-size: 2.5rem;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .user-card {
            background: rgba(131, 241, 0, 0.1);
            border: 1px solid rgba(131, 241, 0, 0.3);
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #83f100;
            color: #0423b2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .user-info-text {
            flex: 1;
        }

        .user-name-sidebar {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }

        .user-email-sidebar {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .sidebar-menu {
            flex: 1;
            padding: 2rem 1rem;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            margin-bottom: 0.5rem;
        }

        .menu-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .menu-item.active {
            background: rgba(131, 241, 0, 0.2);
            border-left: 3px solid #83f100;
        }

        .menu-icon {
            font-size: 1.5rem;
        }

        .sidebar-footer {
            padding: 1rem 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logout-btn {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.875rem;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .main-content {
            margin-left: 280px;
            padding: 3rem;
        }

        .welcome-section {
            margin-bottom: 3rem;
        }

        .welcome-section h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .welcome-section p {
            font-size: 1.125rem;
            opacity: 0.9;
        }

        .date-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.15);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 1rem;
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            font-weight: 600;
        }

        .apps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .app-card {
            background: white;
            color: #1a1a1a;
            padding: 2rem;
            border-radius: 16px;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .app-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        }

        .app-icon {
            font-size: 3rem;
        }

        .app-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0423b2;
        }

        .app-description {
            font-size: 0.95rem;
            color: #666;
            line-height: 1.5;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            border: 2px dashed rgba(255, 255, 255, 0.3);
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
        }

        .empty-state p {
            font-size: 1rem;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
                padding: 1.5rem;
            }

            .apps-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <span class="logo-icon">🚢</span>
            <span class="logo-text">Importe Melhor</span>
        </div>
        
        <div class="user-card">
            <div class="user-avatar"><?php echo strtoupper(substr($session['name'], 0, 1)); ?></div>
            <div class="user-info-text">
                <div class="user-name-sidebar"><?php echo htmlspecialchars($firstName); ?></div>
                <div class="user-email-sidebar"><?php echo htmlspecialchars($session['email']); ?></div>
            </div>
        </div>
    </div>

    <div class="sidebar-menu">
        <a href="/dashboard.php" class="menu-item active">
            <span class="menu-icon">🏠</span>
            <span>Dashboard</span>
        </a>
        <a href="#" class="menu-item">
            <span class="menu-icon">📦</span>
            <span>Ferramentas</span>
        </a>
        <a href="#" class="menu-item">
            <span class="menu-icon">⚙️</span>
            <span>Configurações</span>
        </a>
    </div>

    <div class="sidebar-footer">
        <a href="/logout.php" class="logout-btn">
            <span>🚪</span>
            <span>Sair</span>
        </a>
    </div>
</div>

<div class="main-content">
    <div class="welcome-section">
        <h1>Bem-vindo, <?php echo htmlspecialchars($firstName); ?>! 👋</h1>
        <p>Aqui está um resumo das suas ferramentas disponíveis</p>
        <span class="date-badge"><?php echo date('d/m/Y H:i'); ?></span>
    </div>

    <h2 class="section-title">Suas Ferramentas</h2>

    <?php if (count($applications) > 0): ?>
        <div class="apps-grid">
            <?php foreach ($applications as $app): ?>
                <a href="<?php echo htmlspecialchars($app['app_url']); ?>" class="app-card">
                    <span class="app-icon"><?php echo $app['icon_emoji']; ?></span>
                    <div class="app-name"><?php echo htmlspecialchars($app['app_name']); ?></div>
                    <div class="app-description"><?php echo htmlspecialchars($app['app_description']); ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">🔭</div>
            <h3>Nenhuma ferramenta disponível</h3>
            <p>Entre em contato com o administrador para obter acesso.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
