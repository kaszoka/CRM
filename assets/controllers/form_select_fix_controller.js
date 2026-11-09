import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        const MIN_SPACE_BELOW_PX = 320;

        const isAutocompleteSelect = el => {
            return el.getAttribute('data-ea-widget') === 'ea-autocomplete' || el.classList.contains('tomselected');
        };

        const ensureDropdownVisible = select => {
            if (isAutocompleteSelect(select)) return;
            const rect = select.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            if (spaceBelow < MIN_SPACE_BELOW_PX) {
                select.scrollIntoView({ block: 'center', behavior: 'auto' });
            }
        };

        this.handleMouseDown = e => {
            const select = e.target.closest('select.form-select');
            if (select) ensureDropdownVisible(select);
        };

        this.handleFocus = e => {
            if (e.target.matches && e.target.matches('select.form-select')) {
                ensureDropdownVisible(e.target);
            }
        };

        document.addEventListener('mousedown', this.handleMouseDown, { passive: true });
        document.addEventListener('focusin', this.handleFocus, { passive: true });
    }

    disconnect() {
        if (this.handleMouseDown) {
            document.removeEventListener('mousedown', this.handleMouseDown);
        }
        if (this.handleFocus) {
            document.removeEventListener('focusin', this.handleFocus);
        }
    }
}
