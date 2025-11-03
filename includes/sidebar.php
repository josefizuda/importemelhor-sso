<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="/dashboard.php" class="logo-wrapper">
            <svg class="logo-img" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="logoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#0423b2;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#83f100;stop-opacity:1" />
                    </linearGradient>
                </defs>
                <!-- Ícone de navio estilizado -->
                <path d="M 50 20 L 30 45 L 70 45 Z" fill="url(#logoGradient)"/>
                <path d="M 20 50 L 80 50 L 75 70 L 25 70 Z" fill="url(#logoGradient)" opacity="0.8"/>
                <path d="M 25 75 Q 50 85 75 75" stroke="url(#logoGradient)" stroke-width="3" fill="none"/>
            </svg>
            <span class="logo-text">Importe Melhor</span>
        </a>
    </div>

    <nav class="sidebar-menu">
        <!-- Main Menu -->
        <div class="menu-section">
            <h6 class="menu-section-title">Menu Principal</h6>

            <a href="/dashboard.php" class="menu-item <?php echo (!isset($_GET['page']) || $_GET['page'] === 'dashboard') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <a href="/dashboard.php?page=apps" class="menu-item <?php echo (isset($_GET['page']) && $_GET['page'] === 'apps') ? 'active' : ''; ?>">
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

            <a href="/admin/apps.php" class="menu-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M12 1v6m0 6v6m5.66-13.66l-1.42 1.42m-8.49 8.49l-1.42 1.42M23 12h-6m-6 0H1m18.66 5.66l-1.42-1.42m-8.49-8.49l-1.42-1.42"/>
                </svg>
                <span>Aplicações</span>
            </a>

            <a href="/admin/permissions.php" class="menu-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <span>Permissões</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- Settings -->
        <div class="menu-section">
            <h6 class="menu-section-title">Configurações</h6>

            <a href="/dashboard.php?page=profile" class="menu-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span>Perfil</span>
            </a>

            <a href="/dashboard.php?page=settings" class="menu-item">
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
