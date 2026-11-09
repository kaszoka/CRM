import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const alerts = document.querySelectorAll('.alert[role="alert"], .content-wrapper .alert, article.content .alert, section.content-body .alert');

        alerts.forEach(alert => {
            let type = 'info';
            let icon = 'fa-circle-info';
            if (alert.classList.contains('alert-success')) {
                type = 'success';
                icon = 'fa-circle-check';
            } else if (alert.classList.contains('alert-danger') || alert.classList.contains('alert-error')) {
                type = 'danger';
                icon = 'fa-circle-xmark';
            } else if (alert.classList.contains('alert-warning')) {
                type = 'warning';
                icon = 'fa-triangle-exclamation';
            }

            let text = '';
            alert.childNodes.forEach(node => {
                if (node.nodeType === Node.TEXT_NODE) {
                    text += node.textContent.trim();
                } else if (node.nodeType === Node.ELEMENT_NODE && !node.classList.contains('btn-close') && !node.classList.contains('close')) {
                    text += node.textContent.trim();
                }
            });
            text = text.trim();
            if (!text) return;

            alert.style.display = 'none';

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <i class="fa ${icon} toast-icon"></i>
                <div class="toast-message">${text}</div>
                <button type="button" class="toast-close">&times;</button>
            `;

            toast.querySelector('.toast-close')?.addEventListener('click', () => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            });

            container.appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('show'));

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }
            }, 4000);
        });
    }
}
