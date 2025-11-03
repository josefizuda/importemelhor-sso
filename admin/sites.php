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

// Check permission
$hasPermission = $auth->checkPermission($session['user_id'], 'access_external_sites');
if (!$hasPermission) {
    header('Location: /dashboard.php');
    exit;
}

$firstName = explode(' ', $session['name'])[0];
$isAdmin = ($auth->isAdmin($session['user_id']));
$applications = $auth->getUserApplications($session['user_id']);
$pageTitle = 'Sites da Importe Melhor';

$sites = [
    'main' => [
        'name' => 'Importe Melhor - WP Admin',
        'url' => 'https://importemelhor.com.br/wp-admin',
        'icon' => '🏠',
        'description' => 'Acesse o painel administrativo do site principal da Importe Melhor'
    ],
    'conteudo' => [
        'name' => 'Conteúdo Importe Melhor - WP Admin',
        'url' => 'https://conteudo.importemelhor.com.br/wp-admin',
        'icon' => '📝',
        'description' => 'Gerencie o conteúdo e blog da Importe Melhor'
    ]
];
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
    <style>
        .sites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .site-card {
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2rem;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .site-card:hover {
            border-color: var(--color-primary);
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .site-card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .site-icon {
            font-size: 3rem;
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-gray-100);
            border-radius: var(--radius);
        }

        .site-info h3 {
            margin: 0;
            font-size: 1.25rem;
            color: var(--text-primary);
        }

        .site-url {
            font-size: 0.875rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.25rem;
        }

        .site-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .site-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: auto;
        }

        .btn-access {
            flex: 1;
            padding: 0.75rem 1.5rem;
            background: var(--color-primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-access:hover {
            background: var(--color-primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-copy {
            padding: 0.75rem;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-copy:hover {
            background: var(--color-gray-50);
            border-color: var(--color-primary);
        }

        .info-box {
            margin-top: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
            border-radius: var(--radius-lg);
            color: white;
        }

        .info-box h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.125rem;
        }

        .info-box p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .sites-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <?php include '../includes/sidebar.php'; ?>

        <div class="main-container">
            <?php include '../includes/header.php'; ?>

            <main class="main-content">
                <!-- Page Header -->
                <div style="margin-bottom: 2rem;">
                    <h1 style="margin-bottom: 0.5rem;">Sites da Importe Melhor</h1>
                    <p style="color: var(--text-secondary);">Acesse rapidamente os painéis administrativos dos sites</p>
                </div>

                <!-- Sites Grid -->
                <div class="sites-grid">
                    <?php foreach ($sites as $key => $siteInfo): ?>
                    <div class="site-card">
                        <div class="site-card-header">
                            <div class="site-icon"><?php echo $siteInfo['icon']; ?></div>
                            <div class="site-info">
                                <h3><?php echo htmlspecialchars($siteInfo['name']); ?></h3>
                                <div class="site-url">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="2" y1="12" x2="22" y2="12"/>
                                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                    </svg>
                                    <span><?php echo htmlspecialchars($siteInfo['url']); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="site-description">
                            <?php echo htmlspecialchars($siteInfo['description']); ?>
                        </div>

                        <div class="site-actions">
                            <a href="<?php echo htmlspecialchars($siteInfo['url']); ?>" target="_blank" rel="noopener noreferrer" class="btn-access">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                    <polyline points="15 3 21 3 21 9"/>
                                    <line x1="10" y1="14" x2="21" y2="3"/>
                                </svg>
                                Acessar WP-Admin
                            </a>
                            <button onclick="copyUrl('<?php echo htmlspecialchars($siteInfo['url']); ?>')" class="btn-copy" title="Copiar URL">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Info Box -->
                <div class="info-box">
                    <h3>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: text-bottom; margin-right: 0.5rem;">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="16" x2="12" y2="12"/>
                            <line x1="12" y1="8" x2="12.01" y2="8"/>
                        </svg>
                        Acesso Rápido
                    </h3>
                    <p>Os links acima abrem em uma nova aba para facilitar o acesso. Certifique-se de estar logado nos sites do WordPress para acessar o painel administrativo.</p>
                </div>
            </main>
        </div>
    </div>

    <script>
        function copyUrl(url) {
            navigator.clipboard.writeText(url).then(function() {
                alert('URL copiada: ' + url);
            }).catch(function(err) {
                console.error('Erro ao copiar URL:', err);
            });
        }
    </script>
    <script src="/public/js/main.js"></script>
</body>
</html>
