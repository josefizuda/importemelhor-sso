<?php
require_once 'config.php';

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

$firstName = explode(' ', $session['name'])[0];
$pageTitle = 'Minhas Notificações';
$applications = $auth->getUserApplications($session['user_id']);

// Handle actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'mark_read':
            $notif_id = (int)$_POST['notification_id'];
            $result = $auth->markNotificationAsRead($notif_id, $session['user_id']);
            $message = $result ? 'Notificação marcada como lida!' : 'Erro ao marcar notificação.';
            $messageType = $result ? 'success' : 'error';
            break;

        case 'mark_all_read':
            // Mark all as read
            $notifications = $auth->getUserNotifications($session['user_id'], false);
            foreach ($notifications as $notif) {
                $auth->markNotificationAsRead($notif['id'], $session['user_id']);
            }
            $message = 'Todas as notificações foram marcadas como lidas!';
            $messageType = 'success';
            break;
    }
}

// Get all notifications
$notifications = $auth->getUserNotifications($session['user_id'], true);
$unreadCount = $auth->getUnreadNotificationCount($session['user_id']);
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
        .notification-card {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .notification-card.unread {
            background: var(--color-gray-50);
            border-left: 4px solid var(--color-primary);
        }

        .notification-card:hover {
            box-shadow: var(--shadow-md);
        }

        .notification-card-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .notification-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .notification-card-icon.info {
            background: var(--color-info);
            color: white;
        }

        .notification-card-icon.success {
            background: var(--color-success);
            color: white;
        }

        .notification-card-icon.warning {
            background: var(--color-warning);
            color: white;
        }

        .notification-card-icon.error {
            background: var(--color-error);
            color: white;
        }

        .notification-card-content {
            flex: 1;
        }

        .notification-card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .notification-card-message {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 0.75rem;
        }

        .notification-card-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.875rem;
            color: var(--text-tertiary);
        }

        .notification-card-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--border-color);
        }

        .filter-tab {
            padding: 0.75rem 1.5rem;
            border: none;
            background: transparent;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-secondary);
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: var(--transition);
        }

        .filter-tab:hover {
            color: var(--text-primary);
        }

        .filter-tab.active {
            color: var(--color-primary);
            border-bottom-color: var(--color-primary);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state svg {
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .empty-state h3 {
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .empty-state p {
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-container">
            <?php include 'includes/header.php'; ?>

            <main class="main-content">
                <!-- Page Header -->
                <div class="flex items-center justify-between" style="margin-bottom: 2rem;">
                    <div>
                        <h1 style="margin-bottom: 0.5rem;">Minhas Notificações</h1>
                        <p style="color: var(--text-secondary);">
                            <?php if ($unreadCount > 0): ?>
                                Você tem <?php echo $unreadCount; ?> notificação<?php echo $unreadCount > 1 ? 'ões' : ''; ?> não lida<?php echo $unreadCount > 1 ? 's' : ''; ?>
                            <?php else: ?>
                                Todas as notificações foram lidas
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if ($unreadCount > 0): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="mark_all_read">
                        <button type="submit" class="btn btn-outline">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 6L9 17l-5-5"/>
                            </svg>
                            Marcar todas como lidas
                        </button>
                    </form>
                    <?php endif; ?>
                </div>

                <!-- Message -->
                <?php if ($message): ?>
                <div class="alert" style="margin-bottom: 2rem; padding: 1rem; border-radius: var(--radius); background: <?php echo $messageType === 'success' ? 'var(--color-success)' : 'var(--color-error)'; ?>; color: white;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <button class="filter-tab active" onclick="filterNotifications('all')">
                        Todas (<?php echo count($notifications); ?>)
                    </button>
                    <button class="filter-tab" onclick="filterNotifications('unread')">
                        Não lidas (<?php echo $unreadCount; ?>)
                    </button>
                </div>

                <!-- Notifications List -->
                <div id="notificationsList">
                    <?php if (empty($notifications)): ?>
                    <div class="empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        <h3>Nenhuma notificação</h3>
                        <p>Você não tem notificações no momento.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                        <div class="notification-card <?php echo $notif['is_read'] ? 'read' : 'unread'; ?>"
                             id="notif-<?php echo $notif['id']; ?>"
                             data-read="<?php echo $notif['is_read'] ? 'true' : 'false'; ?>">
                            <div class="notification-card-header">
                                <div class="notification-card-icon <?php echo $notif['type']; ?>">
                                    <?php if ($notif['type'] === 'success'): ?>
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 6L9 17l-5-5"/>
                                    </svg>
                                    <?php elseif ($notif['type'] === 'warning'): ?>
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                        <line x1="12" y1="9" x2="12" y2="13"/>
                                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                                    </svg>
                                    <?php elseif ($notif['type'] === 'error'): ?>
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="15" y1="9" x2="9" y2="15"/>
                                        <line x1="9" y1="9" x2="15" y2="15"/>
                                    </svg>
                                    <?php else: ?>
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="12" y1="16" x2="12" y2="12"/>
                                        <line x1="12" y1="8" x2="12.01" y2="8"/>
                                    </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-card-content">
                                    <div class="notification-card-title">
                                        <?php echo htmlspecialchars($notif['title']); ?>
                                        <?php if (!$notif['is_read']): ?>
                                        <span style="display: inline-block; width: 8px; height: 8px; background: var(--color-primary); border-radius: 50%; margin-left: 0.5rem;"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notification-card-message">
                                        <?php echo nl2br(htmlspecialchars($notif['message'])); ?>
                                    </div>
                                    <div class="notification-card-meta">
                                        <span>
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: text-bottom; display: inline-block; margin-right: 0.25rem;">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12 6 12 12 16 14"/>
                                            </svg>
                                            <?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?>
                                        </span>
                                        <?php if ($notif['created_by_name']): ?>
                                        <span>
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: text-bottom; display: inline-block; margin-right: 0.25rem;">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                                <circle cx="12" cy="7" r="4"/>
                                            </svg>
                                            <?php echo htmlspecialchars($notif['created_by_name']); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!$notif['is_read']): ?>
                                    <div class="notification-card-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                                            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                                Marcar como lida
                                            </button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        function filterNotifications(filter) {
            const cards = document.querySelectorAll('.notification-card');
            const tabs = document.querySelectorAll('.filter-tab');

            // Update active tab
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');

            // Filter cards
            cards.forEach(card => {
                if (filter === 'all') {
                    card.style.display = 'block';
                } else if (filter === 'unread') {
                    card.style.display = card.dataset.read === 'false' ? 'block' : 'none';
                }
            });
        }

        // Scroll to notification if hash is present
        if (window.location.hash) {
            const element = document.querySelector(window.location.hash);
            if (element) {
                setTimeout(() => {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    element.style.boxShadow = '0 0 0 4px var(--color-primary)';
                    setTimeout(() => {
                        element.style.boxShadow = '';
                    }, 2000);
                }, 500);
            }
        }
    </script>
    <script src="/public/js/main.js"></script>
</body>
</html>
