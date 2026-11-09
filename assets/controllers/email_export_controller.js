import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['bar', 'openBtn', 'grid', 'modal', 'textarea', 'selectedCount', 'modalCount', 'copyConfirm', 'pagePickerMenu', 'pagePickerLabel'];

    STORAGE_KEY = 'clientEmailExportSelection';
    BAR_OPEN_KEY = 'clientEmailExportBarOpen';

    connect() {
        this.barVisible = false;
        this.selection = {};

        this.onCheckboxChange = this.onCheckboxChange.bind(this);
        this.onGlobalClick = this.onGlobalClick.bind(this);

        this.loadSelection();
        this.updatePagePickerLabel();

        if (this.isBarOpenStored()) {
            this.applyBarState(true);
        }

        document.querySelectorAll('.email-page-checkbox').forEach(cb => {
            cb.addEventListener('change', () => this.updatePagePickerLabel());
        });

        document.addEventListener('click', this.onGlobalClick);

        window.emailExport = this;
    }

    disconnect() {
        document.removeEventListener('click', this.onGlobalClick);
        if (window.emailExport === this) {
            delete window.emailExport;
        }
    }

    loadSelection() {
        try {
            const raw = sessionStorage.getItem(this.STORAGE_KEY);
            this.selection = raw ? JSON.parse(raw) : {};
        } catch (e) {
            this.selection = {};
        }
    }

    saveSelection() {
        sessionStorage.setItem(this.STORAGE_KEY, JSON.stringify(this.selection));
    }

    updateCount() {
        const count = Object.keys(this.selection).length;
        const el = document.getElementById('email-selected-count');
        if (el) el.textContent = count;
    }

    syncCheckboxesFromSelection() {
        document.querySelectorAll('.client-email-checkbox').forEach(cb => {
            const id = cb.getAttribute('data-client-id');
            const card = cb.closest('.client-card');
            const selected = id && this.selection[id];
            cb.checked = !!selected;
            if (card) {
                if (selected) card.classList.add('email-selected');
                else card.classList.remove('email-selected');
            }
        });
        this.updateCount();
    }

    addClientToSelection(id, email, name) {
        if (!id || !email) return;
        this.selection[String(id)] = { email: email.trim(), name: name || '' };
        this.saveSelection();
        this.updateCount();
    }

    removeClientFromSelection(id) {
        if (!id) return;
        delete this.selection[String(id)];
        this.saveSelection();
        this.updateCount();
    }

    getExportUrl() {
        const bar = document.getElementById('email-export-bar');
        return bar ? bar.getAttribute('data-export-url') : '';
    }

    buildExportRequestUrl(params) {
        const url = new URL(this.getExportUrl(), window.location.href);
        const current = new URL(window.location.href);
        const copied = Object.create(null);

        current.searchParams.forEach((value, key) => {
            if (key === 'page' || key === 'crudAction') return;
            if (!copied[key]) {
                url.searchParams.delete(key);
                copied[key] = true;
            }
            url.searchParams.append(key, value);
        });

        Object.keys(params || {}).forEach(key => {
            const val = params[key];
            if (Array.isArray(val)) {
                url.searchParams.delete(key);
                url.searchParams.delete(key + '[]');
                val.forEach(item => url.searchParams.append(key + '[]', String(item)));
            } else {
                url.searchParams.set(key, val);
            }
        });

        return url.toString();
    }

    fetchClients(params) {
        return fetch(this.buildExportRequestUrl(params), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        }).then(response => {
            if (!response.ok) {
                throw new Error('Nie udało się pobrać listy klientów (HTTP ' + response.status + ').');
            }
            return response.json();
        });
    }

    mergeClientsIntoSelection(clients) {
        clients.forEach(client => {
            this.addClientToSelection(String(client.id), client.email, client.name);
        });
        this.syncCheckboxesFromSelection();
    }

    handleCardClick(event, card, detailUrl) {
        if (!this.barVisible) {
            window.location = detailUrl;
            return;
        }
        if (event.target.closest('.cc-actions') || event.target.closest('.cc-email-check')) {
            return;
        }
        const cb = card.querySelector('.client-email-checkbox');
        if (!cb || card.classList.contains('no-email')) return;
        cb.checked = !cb.checked;
        this.onCheckboxToggle(cb);
    }

    onCheckboxToggle(cb) {
        const card = cb.closest('.client-card');
        const id = cb.getAttribute('data-client-id');
        const email = cb.getAttribute('data-email');
        const name = cb.getAttribute('data-name');
        if (cb.checked) {
            this.addClientToSelection(id, email, name);
            if (card) card.classList.add('email-selected');
        } else {
            this.removeClientFromSelection(id);
            if (card) card.classList.remove('email-selected');
        }
    }

    onCheckboxChange(e) {
        this.onCheckboxToggle(e.target);
    }

    setBarOpen(open) {
        if (open) {
            sessionStorage.setItem(this.BAR_OPEN_KEY, '1');
        } else {
            sessionStorage.removeItem(this.BAR_OPEN_KEY);
        }
    }

    isBarOpenStored() {
        return sessionStorage.getItem(this.BAR_OPEN_KEY) === '1';
    }

    isClientListUrl(href) {
        try {
            const url = new URL(href, window.location.href);
            if (url.origin !== window.location.origin) return false;
            const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
            const nextPath = url.pathname.replace(/\/+$/, '') || '/';
            return nextPath === currentPath;
        } catch (e) {
            return false;
        }
    }

    applyBarState(visible) {
        this.barVisible = visible;
        this.setBarOpen(visible);
        const bar = document.getElementById('email-export-bar');
        const btn = document.getElementById('btn-open-email-export');
        const grid = document.querySelector('.client-cards');

        if (!bar || !btn) return;

        if (this.barVisible) {
            this.loadSelection();
            bar.style.display = 'flex';
            btn.style.display = 'none';
            if (grid) grid.classList.add('email-export-active');
            document.querySelectorAll('.client-email-checkbox').forEach(cb => {
                cb.removeEventListener('change', this.onCheckboxChange);
                cb.addEventListener('change', this.onCheckboxChange);
            });
            this.syncCheckboxesFromSelection();
            this.updatePagePickerLabel();
        } else {
            bar.style.display = 'none';
            btn.style.display = '';
            if (grid) grid.classList.remove('email-export-active');
        }
    }

    toggleBar() {
        this.applyBarState(!this.barVisible);
    }

    getSelectedPages() {
        const pages = [];
        document.querySelectorAll('.email-page-checkbox:checked').forEach(cb => {
            const page = parseInt(cb.value, 10);
            if (page > 0) pages.push(page);
        });
        pages.sort((a, b) => a - b);
        return pages;
    }

    updatePagePickerLabel() {
        const label = document.getElementById('email-page-picker-label');
        if (!label) return;
        const pages = this.getSelectedPages();
        if (pages.length === 0) {
            label.textContent = 'Strony (0)';
            return;
        }
        if (pages.length <= 4) {
            label.textContent = 'Strony: ' + pages.join(', ');
            return;
        }
        label.textContent = 'Strony (' + pages.length + ')';
    }

    togglePagePicker(event) {
        if (event) event.stopPropagation();
        const menu = document.getElementById('email-page-picker-menu');
        if (menu) menu.classList.toggle('open');
    }

    closePagePicker() {
        const menu = document.getElementById('email-page-picker-menu');
        if (menu) menu.classList.remove('open');
    }

    selectAllPagesInPicker() {
        document.querySelectorAll('.email-page-checkbox').forEach(cb => {
            cb.checked = true;
        });
        this.updatePagePickerLabel();
    }

    clearPagesInPicker() {
        document.querySelectorAll('.email-page-checkbox').forEach(cb => {
            cb.checked = false;
        });
        this.updatePagePickerLabel();
    }

    selectSelectedPages() {
        const pages = this.getSelectedPages();
        if (pages.length === 0) {
            alert('Wybierz co najmniej jedną stronę.');
            return;
        }
        this.fetchClients({ pages: pages })
            .then(data => {
                this.mergeClientsIntoSelection(data.clients || []);
                this.closePagePicker();
            })
            .catch(err => {
                alert(err.message || 'Wystąpił błąd podczas zaznaczania stron.');
            });
    }

    selectAllPages() {
        this.fetchClients({ all: '1' })
            .then(data => {
                this.mergeClientsIntoSelection(data.clients || []);
            })
            .catch(err => {
                alert(err.message || 'Wystąpił błąd podczas zaznaczania wszystkich klientów.');
            });
    }

    selectCurrentPage() {
        const bar = document.getElementById('email-export-bar');
        const currentPage = bar ? parseInt(bar.getAttribute('data-current-page') || '1', 10) : 1;
        this.fetchClients({ page_from: currentPage, page_to: currentPage })
            .then(data => {
                this.mergeClientsIntoSelection(data.clients || []);
            })
            .catch(err => {
                alert(err.message || 'Wystąpił błąd podczas zaznaczania bieżącej strony.');
            });
    }

    deselectAll() {
        this.selection = {};
        this.saveSelection();
        document.querySelectorAll('.client-email-checkbox').forEach(cb => {
            cb.checked = false;
            const card = cb.closest('.client-card');
            if (card) card.classList.remove('email-selected');
        });
        this.updateCount();
    }

    getSelectedEmails() {
        const emails = [];
        Object.keys(this.selection).forEach(id => {
            const item = this.selection[id];
            if (item && item.email) emails.push(item.email);
        });
        return emails;
    }

    copyEmails() {
        const emails = this.getSelectedEmails();
        if (emails.length === 0) {
            alert('Nie zaznaczono żadnych klientów z emailem.');
            return;
        }
        this.copyToClipboard(emails.join(', '), null);
    }

    showModal() {
        const emails = this.getSelectedEmails();
        const ta = document.getElementById('email-export-textarea');
        const cnt = document.getElementById('email-modal-count');
        const modal = document.getElementById('email-export-modal');

        if (emails.length === 0) {
            alert('Nie zaznaczono żadnych klientów z emailem.');
            return;
        }

        if (ta) ta.value = emails.join(', ');
        if (cnt) cnt.textContent = emails.length + ' adres' + (emails.length === 1 ? '' : (emails.length < 5 ? 'y' : 'ów')) + ' email';
        if (modal) modal.style.display = 'flex';
        const confirmBtn = document.getElementById('email-copy-confirm');
        if (confirmBtn) confirmBtn.style.display = 'none';
    }

    closeModal() {
        const modal = document.getElementById('email-export-modal');
        if (modal) modal.style.display = 'none';
    }

    copyFromModal() {
        const ta = document.getElementById('email-export-textarea');
        if (!ta) return;
        this.copyToClipboard(ta.value, () => {
            const btn = document.getElementById('email-copy-confirm');
            if (btn) {
                btn.style.display = 'inline';
                setTimeout(() => { btn.style.display = 'none'; }, 2500);
            }
        });
    }

    copyToClipboard(text, onSuccess) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                if (onSuccess) onSuccess();
                else alert('Skopiowano ' + text.split(',').length + ' email(i) do schowka!');
            }).catch(() => this.fallbackCopy(text, onSuccess));
        } else {
            this.fallbackCopy(text, onSuccess);
        }
    }

    fallbackCopy(text, onSuccess) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        if (onSuccess) onSuccess();
        else alert('Skopiowano ' + text.split(',').length + ' email(i) do schowka!');
    }

    onGlobalClick(e) {
        const link = e.target.closest('a[href]');
        if (link) {
            const href = link.getAttribute('href');
            if (href && href.charAt(0) !== '#' && link.target !== '_blank') {
                if (!this.isClientListUrl(link.href)) {
                    this.setBarOpen(false);
                }
            }
        }

        const picker = document.getElementById('email-page-picker');
        if (picker && !picker.contains(e.target)) {
            this.closePagePicker();
        }

        const modal = document.getElementById('email-export-modal');
        if (modal && e.target === modal) {
            this.closeModal();
        }
    }
}
