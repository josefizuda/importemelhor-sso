<?php
require_once '../config.php';

$auth = new Auth();

// Check authentication
if (!isset($_COOKIE['sso_token'])) {
    header('Location: /index.php');
    exit;
}

$session = $auth->validateSession($_COOKIE['sso_token']);

if (!$session) {
    $auth->clearSessionCookie();
    header('Location: /index.php');
    exit;
}

// Check admin permission
$isAdmin = ($auth->isAdmin($session['user_id']));
if (!$isAdmin) {
    header('Location: /dashboard.php');
    exit;
}

$firstName = explode(' ', $session['name'])[0];
$pageTitle = 'Analytics';
$applications = $auth->getUserApplications($session['user_id']);

// Get system statistics
$db = Database::getInstance()->getConnection();

// Total users
$stmt = $db->query("SELECT COUNT(*) as total, COUNT(CASE WHEN is_active THEN 1 END) as active FROM users");
$users_stats = $stmt->fetch();

// Users by role
$stmt = $db->query("SELECT r.name, COUNT(u.id) as count FROM user_roles r LEFT JOIN users u ON u.role_id = r.id GROUP BY r.id, r.name ORDER BY count DESC");
$roles_stats = $stmt->fetchAll();

// Recent logins (last 7 days)
$stmt = $db->query("SELECT DATE(last_login) as date, COUNT(DISTINCT id) as users FROM users WHERE last_login >= NOW() - INTERVAL '7 days' GROUP BY DATE(last_login) ORDER BY date DESC");
$recent_logins = $stmt->fetchAll();

// Total applications
$stmt = $db->query("SELECT COUNT(*) as total, COUNT(CASE WHEN is_active THEN 1 END) as active FROM applications");
$apps_stats = $stmt->fetch();

// Chat statistics
$stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'chat_conversations')");
$chat_enabled = $stmt->fetch()['exists'];
if ($chat_enabled) {
    $stmt = $db->query("SELECT COUNT(*) as conversations, (SELECT COUNT(*) FROM chat_messages) as messages FROM chat_conversations");
    $chat_stats = $stmt->fetch();
} else {
    $chat_stats = ['conversations' => 0, 'messages' => 0];
}

// Check if Google Analytics is configured
$ga_enabled = $auth->getSetting('analytics_enabled', false);
$ga_property = $auth->getSetting('ga4_property_id');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Importe Melhor SSO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/main.css">
</head>
<body>
    <div class="app-wrapper">
        <?php include '../includes/sidebar.php'; ?>

        <div class="main-container">
            <?php include '../includes/header.php'; ?>

            <main class="main-content">
                <!-- Page Header -->
                <div style="margin-bottom: 2rem;">
                    <h1 style="margin-bottom: 0.5rem;">📊 Analytics & Estatísticas</h1>
                    <p style="color: var(--text-secondary);">
                        Métricas e estatísticas do sistema SSO
                        <?php if ($ga_enabled && $ga_property): ?>
                            · Google Analytics: <span style="color: var(--color-success); font-weight: 600;">Conectado</span>
                        <?php else: ?>
                            · <a href="/admin/integrations.php" style="color: var(--color-warning);">Configurar Google Analytics</a>
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Stats Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <!-- Total Users -->
                    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <div class="card-body">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <p style="opacity: 0.9; margin-bottom: 0.5rem;">Total de Usuários</p>
                                    <h2 style="font-size: 2.5rem; font-weight: 700; margin: 0;"><?php echo $users_stats['total']; ?></h2>
                                    <p style="opacity: 0.8; font-size: 0.875rem; margin-top: 0.5rem;">
                                        <?php echo $users_stats['active']; ?> ativos
                                    </p>
                                </div>
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity: 0.5;">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Applications -->
                    <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                        <div class="card-body">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <p style="opacity: 0.9; margin-bottom: 0.5rem;">Aplicações</p>
                                    <h2 style="font-size: 2.5rem; font-weight: 700; margin: 0;"><?php echo $apps_stats['total']; ?></h2>
                                    <p style="opacity: 0.8; font-size: 0.875rem; margin-top: 0.5rem;">
                                        <?php echo $apps_stats['active']; ?> ativas
                                    </p>
                                </div>
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity: 0.5;">
                                    <rect x="3" y="3" width="7" height="7"/>
                                    <rect x="14" y="3" width="7" height="7"/>
                                    <rect x="14" y="14" width="7" height="7"/>
                                    <rect x="3" y="14" width="7" height="7"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Messages -->
                    <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                        <div class="card-body">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <p style="opacity: 0.9; margin-bottom: 0.5rem;">Mensagens</p>
                                    <h2 style="font-size: 2.5rem; font-weight: 700; margin: 0;"><?php echo $chat_stats['messages']; ?></h2>
                                    <p style="opacity: 0.8; font-size: 0.875rem; margin-top: 0.5rem;">
                                        <?php echo $chat_stats['conversations']; ?> conversas
                                    </p>
                                </div>
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity: 0.5;">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Roles -->
                    <div class="card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                        <div class="card-body">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <p style="opacity: 0.9; margin-bottom: 0.5rem;">Roles</p>
                                    <h2 style="font-size: 2.5rem; font-weight: 700; margin: 0;"><?php echo count($roles_stats); ?></h2>
                                    <p style="opacity: 0.8; font-size: 0.875rem; margin-top: 0.5rem;">
                                        tipos de usuário
                                    </p>
                                </div>
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity: 0.5;">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Grid -->
                <div class="grid grid-cols-2" style="gap: 1.5rem;">
                    <!-- Users by Role -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Usuários por Role</h2>
                        </div>
                        <div class="card-body">
                            <?php foreach ($roles_stats as $role): ?>
                            <div style="margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span><?php echo htmlspecialchars($role['name'] ?: 'Sem Role'); ?></span>
                                    <strong><?php echo $role['count']; ?></strong>
                                </div>
                                <div style="background: var(--color-gray-200); height: 8px; border-radius: 4px; overflow: hidden;">
                                    <div style="background: var(--color-primary); height: 100%; width: <?php echo $users_stats['total'] > 0 ? ($role['count'] / $users_stats['total'] * 100) : 0; ?>%;"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Atividade Recente (7 dias)</h2>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_logins)): ?>
                                <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">Nenhuma atividade recente</p>
                            <?php else: ?>
                                <?php foreach ($recent_logins as $login): ?>
                                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                                    <span style="color: var(--text-secondary);">
                                        <?php echo date('d/m/Y', strtotime($login['date'])); ?>
                                    </span>
                                    <strong><?php echo $login['users']; ?> logins</strong>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="/public/js/main.js"></script>
</body>
</html>
