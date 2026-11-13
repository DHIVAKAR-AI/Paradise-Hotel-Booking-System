// Immediate theme initialization to prevent flash of wrong theme
(function () {
    const savedTheme = localStorage.getItem('hotel_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
})();

document.addEventListener('DOMContentLoaded', function () {
    // Inject floating theme switcher if not manually created
    if (!document.getElementById('globalThemeToggle')) {
        const toggleBtn = document.createElement('button');
        toggleBtn.id = 'globalThemeToggle';
        toggleBtn.className = 'theme-toggle-fab';
        toggleBtn.setAttribute('type', 'button');
        toggleBtn.setAttribute('aria-label', 'Toggle theme');
        toggleBtn.setAttribute('title', 'Switch Theme (Dark / Light)');
        toggleBtn.innerHTML = `
            <span class="theme-icon-container">
                <i class="bi bi-sun-fill icon-sun"></i>
                <i class="bi bi-moon-stars-fill icon-moon"></i>
            </span>
            <span class="theme-text">Mode</span>
        `;
        document.body.appendChild(toggleBtn);
        toggleBtn.addEventListener('click', toggleTheme);
    }

    // Attach listener to any in-page theme toggle buttons
    document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
        btn.addEventListener('click', toggleTheme);
    });

    updateThemeUI(document.documentElement.getAttribute('data-theme') || 'dark');
});

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('hotel_theme', newTheme);
    updateThemeUI(newTheme);
}

function updateThemeUI(theme) {
    const isDark = theme === 'dark';
    const allButtons = document.querySelectorAll('#globalThemeToggle, .theme-toggle-btn');
    
    allButtons.forEach(btn => {
        btn.setAttribute('aria-label', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
        btn.setAttribute('title', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
        const textSpan = btn.querySelector('.theme-text');
        if (textSpan) {
            textSpan.textContent = isDark ? 'Light' : 'Dark';
        }
    });

    // Also dispatch custom event for any page-specific dynamic handlers
    window.dispatchEvent(new CustomEvent('themechanged', { detail: { theme } }));
}

