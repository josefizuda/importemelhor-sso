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
                <!-- Coming Soon Section -->
                <div style="display: flex; align-items: center; justify-content: center; min-height: 60vh;">
                    <div style="text-align: center; max-width: 500px;">
                        <div style="margin-bottom: 2rem;">
                            <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto; color: var(--color-primary);">
                                <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
                                <path d="M22 12A10 10 0 0 0 12 2v10z"/>
                            </svg>
                        </div>

                        <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                            Analytics
                        </h1>

                        <div style="display: inline-block; padding: 0.5rem 1.5rem; background: var(--color-warning); color: white; border-radius: 9999px; font-weight: 700; margin-bottom: 1.5rem;">
                            EM BREVE
                        </div>

                        <p style="color: var(--text-secondary); font-size: 1.125rem; line-height: 1.75; margin-bottom: 2rem;">
                            Estamos trabalhando em um painel completo de analytics com métricas de uso, relatórios detalhados e insights sobre o sistema.
                        </p>

                        <div style="background: var(--bg-secondary); border-radius: var(--radius); padding: 1.5rem; text-align: left;">
                            <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem; color: var(--text-primary);">
                                Recursos Planejados:
                            </h3>
                            <ul style="list-style: none; padding: 0; margin: 0; color: var(--text-secondary);">
                                <li style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.75rem;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--color-success); flex-shrink: 0;">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Dashboard com métricas em tempo real
                                </li>
                                <li style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.75rem;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--color-success); flex-shrink: 0;">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Relatórios de acesso e uso de aplicações
                                </li>
                                <li style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.75rem;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--color-success); flex-shrink: 0;">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Estatísticas de usuários ativos
                                </li>
                                <li style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.75rem;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--color-success); flex-shrink: 0;">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Gráficos de tendências e comportamento
                                </li>
                                <li style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.75rem;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--color-success); flex-shrink: 0;">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Exportação de dados e relatórios
                                </li>
                            </ul>
                        </div>

                        <div style="margin-top: 2rem;">
                            <a href="/dashboard.php" class="btn btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                                </svg>
                                Voltar ao Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="/public/js/main.js"></script>
</body>
</html>
