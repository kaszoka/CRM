import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.handleClick = this.handleClick.bind(this);
        this.element.addEventListener('click', this.handleClick);
    }

    disconnect() {
        this.element.removeEventListener('click', this.handleClick);
    }

    handleClick(e) {
        const row = e.target.closest('tr[data-id]');
        if (!row) return;

        const isInteractive = e.target.closest('a, button, input, select, textarea, .form-switch, .action-detail, .action-edit, .action-delete, .form-check');
        if (isInteractive) return;

        const detailLink = row.querySelector('a.action-detail');
        if (detailLink && detailLink.getAttribute('href')) {
            window.location.href = detailLink.getAttribute('href');
        }
    }
}
