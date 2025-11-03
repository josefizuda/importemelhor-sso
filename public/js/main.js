/**
 * Main JavaScript - Importe Melhor SSO
 * Theme toggle and mobile menu functionality
 */

// Theme Toggle
class ThemeManager {
    constructor() {
        this.theme = localStorage.getItem('theme') || 'light';
        this.init();
    }

    init() {
        // Apply saved theme
        this.applyTheme(this.theme);

        // Setup theme toggle button
        this.setupToggle();
    }

    applyTheme(theme) {
        if (theme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
        this.theme = theme;
        localStorage.setItem('theme', theme);

        // Update icon
        this.updateIcon();
    }

    updateIcon() {
        const toggleBtn = document.getElementById('themeToggle');
        if (!toggleBtn) return;

        const isDark = this.theme === 'dark';

        toggleBtn.innerHTML = isDark ? `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
        ` : `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
        `;

        toggleBtn.title = isDark ? 'Mudar para tema claro' : 'Mudar para tema escuro';
    }

    toggle() {
        const newTheme = this.theme === 'light' ? 'dark' : 'light';
        this.applyTheme(newTheme);
    }

    setupToggle() {
        const toggleBtn = document.getElementById('themeToggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => this.toggle());
        }
    }
}

// Mobile Menu
class MobileMenu {
    constructor() {
        this.sidebar = document.getElementById('sidebar');
        this.menuToggle = document.getElementById('mobileMenuToggle');
        this.init();
    }

    init() {
        if (!this.sidebar || !this.menuToggle) return;

        // Toggle button click
        this.menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggle();
        });

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 1024) {
                if (!this.sidebar.contains(e.target) && !this.menuToggle.contains(e.target)) {
                    this.close();
                }
            }
        });

        // Close when clicking on a menu item
        const menuItems = this.sidebar.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', () => {
                if (window.innerWidth <= 1024) {
                    this.close();
                }
            });
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024) {
                this.sidebar.classList.remove('mobile-open');
            }
        });
    }

    toggle() {
        this.sidebar.classList.toggle('mobile-open');
    }

    close() {
        this.sidebar.classList.remove('mobile-open');
    }

    open() {
        this.sidebar.classList.add('mobile-open');
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.themeManager = new ThemeManager();
    window.mobileMenu = new MobileMenu();
});
