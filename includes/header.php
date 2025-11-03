<?php
// Get notifications count
$unreadCount = 0;
$notifications = [];
if (isset($auth) && isset($session)) {
    $unreadCount = $auth->getUnreadNotificationCount($session['user_id']);
    $notifications = $auth->getUserNotifications($session['user_id'], false);
}
?>
<!-- Header -->
<header class="header">
    <div class="header-left">
        <button class="mobile-menu-toggle" id="mobileMenuToggle" style="display: none;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 12h18M3 6h18M3 18h18"/>
            </svg>
        </button>

        <nav class="breadcrumb">
            <span class="breadcrumb-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                </svg>
                Dashboard
            </span>
            <?php if (isset($pageTitle) && $pageTitle !== 'Dashboard'): ?>
            <span>/</span>
            <span class="breadcrumb-item active"><?php echo htmlspecialchars($pageTitle); ?></span>
            <?php endif; ?>
        </nav>
    </div>

    <div class="header-right">
        <!-- Dark Mode Toggle -->
        <button class="theme-toggle" id="themeToggle" title="Alternar tema">
            <svg class="sun-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
        </button>

        <!-- Notifications -->
        <div style="position: relative;">
            <button class="icon-btn" id="notificationBtn" title="Notificações" onclick="toggleNotifications()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <?php if ($unreadCount > 0): ?>
                <span class="notification-badge" id="notificationBadge"><?php echo $unreadCount; ?></span>
                <?php endif; ?>
            </button>

            <!-- Notifications Dropdown -->
            <div id="notificationsDropdown" class="notifications-dropdown" style="display: none;">
                <div class="notifications-header">
                    <h3>Notificações</h3>
                    <?php if ($unreadCount > 0): ?>
                    <span style="color: var(--text-secondary); font-size: 0.875rem;"><?php echo $unreadCount; ?> não lidas</span>
                    <?php endif; ?>
                </div>
                <div class="notifications-list">
                    <?php if (empty($notifications)): ?>
                    <div class="notification-item" style="text-align: center; color: var(--text-secondary);">
                        Nenhuma notificação
                    </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                        <div class="notification-item <?php echo $notif['is_read'] ? 'read' : 'unread'; ?>" onclick="markAsRead(<?php echo $notif['id']; ?>)">
                            <div class="notification-icon notification-<?php echo $notif['type']; ?>">
                                <?php if ($notif['type'] === 'success'): ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 6L9 17l-5-5"/>
                                </svg>
                                <?php elseif ($notif['type'] === 'warning'): ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                                <?php elseif ($notif['type'] === 'error'): ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="15" y1="9" x2="9" y2="15"/>
                                    <line x1="9" y1="9" x2="15" y2="15"/>
                                </svg>
                                <?php else: ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="16" x2="12" y2="12"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                </svg>
                                <?php endif; ?>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                                <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                                <div class="notification-time"><?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- User Menu -->
        <a href="/settings.php" class="user-menu" style="text-decoration: none; color: inherit; cursor: pointer;">
            <div class="user-avatar"><?php echo strtoupper(substr($session['name'], 0, 1)); ?></div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars(explode(' ', $session['name'])[0]); ?></div>
                <div class="user-role">
                    <?php
                    $isAdmin = ($session['email'] === 'app@importemelhor.com.br');
                    echo $isAdmin ? 'Administrador' : 'Usuário';
                    ?>
                </div>
            </div>
        </a>
    </div>
</header>

<style>
.icon-btn {
    position: relative;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: transparent;
    border: 1px solid var(--color-gray-200);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
    color: var(--color-gray-600);
}

.icon-btn:hover {
    background: var(--color-gray-50);
    border-color: var(--color-gray-300);
}

.notification-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    width: 18px;
    height: 18px;
    background: var(--color-error);
    color: var(--color-white);
    border-radius: 50%;
    font-size: 0.625rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notifications-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 380px;
    max-height: 500px;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    z-index: 1000;
}

.notifications-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.notifications-header h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    color: var(--text-primary);
}

.notifications-list {
    max-height: 400px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    transition: var(--transition);
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item:hover {
    background: var(--color-gray-50);
}

.notification-item.unread {
    background: var(--color-gray-50);
}

.notification-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.notification-info {
    background: var(--color-info);
    color: white;
}

.notification-success {
    background: var(--color-success);
    color: white;
}

.notification-warning {
    background: var(--color-warning);
    color: white;
}

.notification-error {
    background: var(--color-error);
    color: white;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.notification-message {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
}

.notification-time {
    font-size: 0.75rem;
    color: var(--text-tertiary);
}

.user-menu:hover {
    opacity: 0.8;
}

@media (max-width: 1024px) {
    .mobile-menu-toggle {
        display: flex !important;
        width: 40px;
        height: 40px;
        border-radius: var(--radius);
        background: transparent;
        border: 1px solid var(--color-gray-200);
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: var(--color-gray-700);
    }

    .notifications-dropdown {
        width: 320px;
    }
}
</style>

<script>
function toggleNotifications() {
    const dropdown = document.getElementById('notificationsDropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

function markAsRead(notificationId) {
    fetch('/api/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to update counts
            window.location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('notificationsDropdown');
    const btn = document.getElementById('notificationBtn');
    if (dropdown && btn && !dropdown.contains(e.target) && !btn.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});
</script>
