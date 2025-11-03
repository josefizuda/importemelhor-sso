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
$isAdmin = ($session['email'] === 'app@importemelhor.com.br');
$applications = $auth->getUserApplications($session['user_id']);
$pageTitle = 'Sites da Importe Melhor';

// Get selected site
$site = $_GET['site'] ?? 'main';

$sites = [
    'main' => [
        'name' => 'Importe Melhor',
        'url' => 'https://importemelhor.com.br',
        'icon' => '🏠'
    ],
    'conteudo' => [
        'name' => 'Conteúdo Importe Melhor',
        'url' => 'https://conteudo.importemelhor.com.br',
        'icon' => '📝'
    ]
];

$currentSite = $sites[$site] ?? $sites['main'];
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
        .site-selector {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .site-btn {
            flex: 1;
            padding: 1rem;
            border-radius: var(--radius);
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 500;
        }

        .site-btn:hover {
            border-color: var(--color-primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .site-btn.active {
            border-color: var(--color-primary);
            background: var(--color-primary);
            color: white;
        }

        .site-icon {
            font-size: 1.5rem;
        }

        .iframe-container {
            position: relative;
            width: 100%;
            height: calc(100vh - 250px);
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }

        .iframe-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .iframe-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: var(--text-secondary);
        }

        .iframe-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background: var(--color-gray-100);
            border-bottom: 1px solid var(--border-color);
        }

        .iframe-url {
            font-size: 0.875rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .refresh-btn {
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .refresh-btn:hover {
            background: var(--color-gray-50);
            border-color: var(--color-primary);
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
                    <p style="color: var(--text-secondary);">Acesse e gerencie os sites da Importe Melhor</p>
                </div>

                <!-- Site Selector -->
                <div class="site-selector">
                    <?php foreach ($sites as $key => $siteInfo): ?>
                    <a href="?site=<?php echo $key; ?>" class="site-btn <?php echo $site === $key ? 'active' : ''; ?>">
                        <span class="site-icon"><?php echo $siteInfo['icon']; ?></span>
                        <span><?php echo $siteInfo['name']; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- iframe Container -->
                <div class="iframe-container">
                    <div class="iframe-toolbar">
                        <div class="iframe-url">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="2" y1="12" x2="22" y2="12"/>
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                            </svg>
                            <span><?php echo $currentSite['url']; ?></span>
                        </div>
                        <button class="refresh-btn" onclick="refreshIframe()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
                            </svg>
                            Atualizar
                        </button>
                    </div>
                    <div class="iframe-loading" id="loading">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                        </svg>
                        <p>Carregando...</p>
                    </div>
                    <iframe
                        id="siteFrame"
                        src="<?php echo htmlspecialchars($currentSite['url']); ?>"
                        onload="document.getElementById('loading').style.display='none'"
                        sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-modals"
                        style="display: none;">
                    </iframe>
                </div>

                <div style="margin-top: 1rem; padding: 1rem; background: var(--color-gray-100); border-radius: var(--radius); font-size: 0.875rem; color: var(--text-secondary);">
                    <strong>Nota:</strong> Você está acessando <?php echo $currentSite['name']; ?> através de um iframe seguro. Algumas funcionalidades podem ser limitadas por motivos de segurança.
                </div>
            </main>
        </div>
    </div>

    <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>

    <script>
        function refreshIframe() {
            const iframe = document.getElementById('siteFrame');
            const loading = document.getElementById('loading');
            loading.style.display = 'block';
            iframe.src = iframe.src;
        }

        // Show iframe after loading
        document.getElementById('siteFrame').addEventListener('load', function() {
            this.style.display = 'block';
            document.getElementById('loading').style.display = 'none';
        });

        // Handle loading errors
        document.getElementById('siteFrame').addEventListener('error', function() {
            document.getElementById('loading').innerHTML = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg><p>Erro ao carregar o site</p>';
        });
    </script>
    <script src="/public/js/main.js"></script>
</body>
</html>
