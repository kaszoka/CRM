import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        const FIELD_LABELS = {
            industries: 'Dodaj branżę',
            assortments: 'Dodaj asortyment',
            tags: 'Dodaj tag',
            sources: 'Dodaj źródło'
        };

        const getLabel = (el) => {
            if (!el) return null;
            const all = [(el.name || ''), (el.getAttribute('data-ea-autocomplete-endpoint-url') || ''), (el.id || '')]
                .join(' ')
                .toLowerCase();
            const key = Object.keys(FIELD_LABELS).find(k => all.includes(k));
            if (key) return FIELD_LABELS[key];
            if (el.getAttribute('data-ea-autocomplete-allow-item-create') === 'true') return 'Dodaj';
            return null;
        };

        const renderOptionCreate = (label) => {
            return (data, escape) => {
                return '<div class="create ea-autocomplete-create-option">'
                    + '<i class="fa fa-plus"></i> '
                    + escape(label) + ': <strong>' + escape(data.input) + '</strong></div>';
            };
        };

        const applyOptionCreate = (ts, label) => {
            if (ts._plLabelApplied) return;
            ts._plLabelApplied = true;
            if (!ts.settings.render) ts.settings.render = {};
            ts.settings.render.option_create = renderOptionCreate(label);
        };

        this.handlePreConnect = (event) => {
            const label = getLabel(event.target);
            if (!label) return;
            const config = event.detail.config;
            config.render = config.render || {};
            config.render.option_create = renderOptionCreate(label);
        };

        this.handleConnect = (event) => {
            const label = getLabel(event.target);
            if (!label || !event.detail.tomSelect) return;
            applyOptionCreate(event.detail.tomSelect, label);
        };

        document.addEventListener('ea.autocomplete.pre-connect', this.handlePreConnect);
        document.addEventListener('ea.autocomplete.connect', this.handleConnect);

        document.querySelectorAll('[data-ea-autocomplete-allow-item-create="true"]').forEach(el => {
            const label = getLabel(el);
            if (label && el.tomselect) applyOptionCreate(el.tomselect, label);
        });
    }

    disconnect() {
        if (this.handlePreConnect) {
            document.removeEventListener('ea.autocomplete.pre-connect', this.handlePreConnect);
        }
        if (this.handleConnect) {
            document.removeEventListener('ea.autocomplete.connect', this.handleConnect);
        }
    }
}
