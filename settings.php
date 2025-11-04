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

$firstName = explode(' ', $session['name'])[0];
$isAdmin = ($auth->isAdmin($session['user_id']));
$applications = $auth->getUserApplications($session['user_id']);

$pageTitle = 'Configurações';
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
                <div style="margin-bottom: 2rem;">
                    <h1 style="margin-bottom: 0.5rem;">Configurações</h1>
                    <p style="color: var(--text-secondary); margin: 0;">
                        Gerencie suas preferências e configurações
                    </p>
                </div>

                <!-- Settings Cards -->
                <div class="grid grid-cols-1" style="gap: 1.5rem;">
                    <!-- Perfil -->
                    <div class="card">
                        <h2 class="card-title">Informações do Perfil</h2>
                        <div class="card-body">
                            <div class="grid grid-cols-2" style="gap: 1rem; margin-top: 1rem;">
                                <div>
                                    <label style="font-size: 0.875rem; font-weight: 600; color: var(--text-secondary); display: block; margin-bottom: 0.5rem;">Nome</label>
                                    <p style="margin: 0;"><?php echo htmlspecialchars($session['name']); ?></p>
                                </div>
                                <div>
                                    <label style="font-size: 0.875rem; font-weight: 600; color: var(--text-secondary); display: block; margin-bottom: 0.5rem;">Email</label>
                                    <p style="margin: 0;"><?php echo htmlspecialchars($session['email']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tema -->
                    <div class="card">
                        <h2 class="card-title">Aparência</h2>
                        <div class="card-body">
                            <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                                Escolha o tema de sua preferência
                            </p>
                            <div style="display: flex; gap: 1rem;">
                                <button onclick="setTheme('light')" class="btn btn-outline">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="5"/>
                                        <line x1="12" y1="1" x2="12" y2="3"/>
                                        <line x1="12" y1="21" x2="12" y2="23"/>
                                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                                        <line x1="1" y1="12" x2="3" y2="12"/>
                                        <line x1="21" y1="12" x2="23" y2="12"/>
                                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                                    </svg>
                                    Tema Claro
                                </button>
                                <button onclick="setTheme('dark')" class="btn btn-outline">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                                    </svg>
                                    Tema Escuro
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Segurança -->
                    <div class="card">
                        <h2 class="card-title">Segurança</h2>
                        <div class="card-body">
                            <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                                Gerencie suas sessões e segurança
                            </p>
                            <a href="/logout.php" class="btn" style="background: var(--color-error); color: white;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                    <polyline points="16 17 21 12 16 7"/>
                                    <line x1="21" y1="12" x2="9" y2="12"/>
                                </svg>
                                Sair de todas as sessões
                            </a>
                        </div>
                    </div>

                    <?php if ($isAdmin): ?>
                    <!-- Integrações (apenas admin) -->
                    <div class="card">
                        <h2 class="card-title">🔗 Integrações</h2>
                        <div class="card-body">
                            <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                                Configure integrações com serviços externos (Google Analytics, Facebook Pixel, Google Ads, reCAPTCHA)
                            </p>
                            <?php
                            // Check if integrations table exists
                            $db = Database::getInstance()->getConnection();
                            try {
                                $stmt = $db->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'system_settings')");
                                $table_exists = $stmt->fetch()['exists'];

                                if ($table_exists) {
                                    $stmt = $db->query("SELECT COUNT(*) as count FROM system_settings");
                                    $count = $stmt->fetch()['count'];

                                    if ($count > 0) {
                                        echo '<div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 4px; border-left: 4px solid #28a745; margin-bottom: 1rem;">';
                                        echo '<strong>✓ Tabela de integrações configurada!</strong><br>';
                                        echo 'Total de configurações: ' . $count;
                                        echo '</div>';
                                        echo '<a href="/admin/integrations.php" class="btn btn-primary">Gerenciar Integrações</a>';
                                    } else {
                                        echo '<div style="background: #fff3cd; color: #856404; padding: 1rem; border-radius: 4px; border-left: 4px solid #ffc107; margin-bottom: 1rem;">';
                                        echo '<strong>⚠️ Tabela existe mas está vazia</strong><br>';
                                        echo 'Execute a migração para criar as configurações padrão.';
                                        echo '</div>';
                                        echo '<a href="/database/run_migration.php?type=integrations" class="btn btn-primary">Executar Migração de Integrações</a>';
                                    }
                                } else {
                                    echo '<div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 4px; border-left: 4px solid #dc3545; margin-bottom: 1rem;">';
                                    echo '<strong>✗ Tabela de integrações não existe</strong><br>';
                                    echo 'Execute a migração para criar a estrutura necessária.';
                                    echo '</div>';
                                    echo '<a href="/database/run_migration.php?type=integrations" class="btn" style="background: #dc3545; color: white;">Criar Tabela de Integrações</a>';
                                }
                            } catch (PDOException $e) {
                                echo '<div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 4px; border-left: 4px solid #dc3545;">';
                                echo '<strong>Erro ao verificar integrações:</strong> ' . htmlspecialchars($e->getMessage());
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="/public/js/main.js"></script>
    <script>
        function setTheme(theme) {
            if (window.themeManager) {
                window.themeManager.applyTheme(theme);
            }
        }
    </script>
<?php include 'includes/chat_widget.php'; ?>
</body>
</html>
