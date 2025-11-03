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
        <button class="icon-btn" title="Notificações">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span class="notification-badge">3</span>
        </button>

        <!-- User Menu -->
        <div class="user-menu">
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
        </div>
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
}
</style>
