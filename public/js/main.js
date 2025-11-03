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
