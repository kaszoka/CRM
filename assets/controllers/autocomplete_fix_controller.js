import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        const DROPDOWN_MIN_HEIGHT = 220;

        const applyDirection = (ts) => {
            const control = ts.control;
            const dropdown = ts.dropdown;
            if (!control || !dropdown) return;

            const rect = control.getBoundingClientRect();
            const scrollContainer = control.closest('.modal-content') || control.closest('.modal-body');
            const containerBottom = scrollContainer ? scrollContainer.getBoundingClientRect().bottom : window.innerHeight;
            const containerTop = scrollContainer ? scrollContainer.getBoundingClientRect().top : 0;

            const spaceBelow = containerBottom - rect.bottom;
            const spaceAbove = rect.top - containerTop;

            if (spaceBelow < DROPDOWN_MIN_HEIGHT && spaceAbove > spaceBelow) {
                dropdown.classList.add('ts-dropdown-up');
                dropdown.classList.remove('ts-dropdown-down');
            } else {
                dropdown.classList.remove('ts-dropdown-up');
                dropdown.classList.add('ts-dropdown-down');
            }
        };

        const patchTomSelect = (ts) => {
            if (!ts || ts._crmpPatched) return;
            ts._crmpPatched = true;

            ts.settings.shouldLoad = () => true;

            ts.on('type', (str) => {
                if (str === '') {
                    ts.loadedSearches = {};
                }
            });

            ts.positionDropdown = () => {};

            ts.on('dropdown_open', () => {
                requestAnimationFrame(() => applyDirection(ts));
            });

            const reposition = () => {
                if (ts.isOpen) {
                    requestAnimationFrame(() => applyDirection(ts));
                }
            };
            window.addEventListener('resize', reposition, { passive: true });
            window.addEventListener('scroll', reposition, { passive: true, capture: true });
        };

        this.handleConnect = (event) => {
            patchTomSelect(event.detail?.tomSelect);
        };

        document.addEventListener('ea.autocomplete.connect', this.handleConnect);

        document.querySelectorAll('[data-ea-widget="ea-autocomplete"]').forEach(el => {
            if (el.tomselect) patchTomSelect(el.tomselect);
        });
    }

    disconnect() {
        if (this.handleConnect) {
            document.removeEventListener('ea.autocomplete.connect', this.handleConnect);
        }
    }
}
