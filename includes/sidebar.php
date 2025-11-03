<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="/dashboard.php" class="logo-wrapper">
            <img src="https://conteudo.importemelhor.com.br/wp-content/uploads/2025/11/LogoHazul.png"
                 alt="Importe Melhor"
                 class="logo-img-real"
                 style="max-width: 180px; height: auto;">
        </a>
    </div>

    <nav class="sidebar-menu">
        <!-- Main Menu -->
        <div class="menu-section">
            <h6 class="menu-section-title">Menu Principal</h6>

            <a href="/dashboard.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <a href="/apps.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) === 'apps.php') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
                <span>Ferramentas</span>
                <span class="menu-badge"><?php echo count($applications ?? []); ?></span>
            </a>
        </div>

        <?php
        $isAdmin = ($session['email'] === 'app@importemelhor.com.br');
        if ($isAdmin):
        ?>
        <!-- Admin Menu -->
        <div class="menu-section">
            <h6 class="menu-section-title">Administração</h6>

            <a href="/admin/banners.php" class="menu-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                </svg>
                <span>Banners</span>
            </a>

            <a href="/admin/users.php" class="menu-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>Usuários</span>
            </a>

            <a href="/admin/roles.php" class="menu-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <span>Roles/Permissões</span>
            </a>

            <a href="/admin/notifications.php" class="menu-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span>Notificações</span>
            </a>

            <a href="/admin/sites.php" class="menu-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="2" y1="12" x2="22" y2="12"/>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                </svg>
                <span>Sites IM</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- Settings -->
        <div class="menu-section">
            <h6 class="menu-section-title">Configurações</h6>

            <a href="/settings.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) === 'settings.php') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M12 1v6m0 6v6m5.66-13.66l-1.42 1.42m-8.49 8.49l-1.42 1.42M23 12h-6m-6 0H1m18.66 5.66l-1.42-1.42m-8.49-8.49l-1.42-1.42"/>
                </svg>
                <span>Configurações</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <a href="/logout.php" class="logout-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <span>Sair</span>
        </a>
    </div>
</aside>

<script>
// Mobile menu toggle
const mobileMenuToggle = document.getElementById('mobileMenuToggle');
const sidebar = document.getElementById('sidebar');

if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', (e) => {
    if (window.innerWidth <= 1024) {
        if (!sidebar.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    }
});
</script>
