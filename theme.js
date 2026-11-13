// Immediate theme initialization to prevent flash of wrong theme
(function () {
    const savedTheme = localStorage.getItem('hotel_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
})();

document.addEventListener('DOMContentLoaded', function () {
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
    const allButtons = document.querySelectorAll('.theme-toggle-btn');
    
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

