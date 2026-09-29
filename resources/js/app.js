import * as bootstrap from 'bootstrap/dist/js/bootstrap.bundle.min.js';
window.bootstrap = bootstrap;
import '../css/app.css';
import Chart from 'chart.js/auto';
window.Chart = Chart;
import QRCode from 'qrcode';
window.QRCode = QRCode;

(function () {
    const KEY = 'rowad-…heme';
    const apply = (theme) => {
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.setAttribute('data-bs-theme', theme);
        document.querySelectorAll('.theme-toggle-btn').forEach((btn) => {
            const icon = btn.querySelector('i');
            if (icon) icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
            btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
        });
    };
    apply(localStorage.getItem(KEY) || 'light');

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.theme-toggle-btn');
        if (!btn) return;
        const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        localStorage.setItem(KEY, next);
        apply(next);
    });
})();
