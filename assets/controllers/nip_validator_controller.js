import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.submitIntercepted = false;
        this.initModal();
        this.initCounter();
        this.bindEvents();

        window.showModal = (nipInput, v, len, form) => this.showConfirmModal(nipInput, v, len, form);
    }

    disconnect() {
        this.unbindEvents();
        if (window.showModal) delete window.showModal;
    }

    get nipInput() {
        return document.getElementById('Client_nip');
    }

    initModal() {
        if (document.getElementById('nip-confirm-overlay')) return;

        const modalHtml = `
            <div id="nip-confirm-overlay">
                <div id="nip-confirm-box">
                    <div class="nip-modal-icon">⚠️</div>
                    <h3>Niestandardowa długość NIP</h3>
                    <p id="nip-confirm-text"></p>
                    <div class="nip-modal-btns">
                        <button id="nip-btn-cancel" type="button">Anuluj — popraw NIP</button>
                        <button id="nip-btn-confirm" type="button">Tak, zapisz mimo to</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        document.getElementById('nip-btn-cancel')?.addEventListener('click', () => {
            document.getElementById('nip-confirm-overlay')?.classList.remove('active');
            this.submitIntercepted = false;
            this.nipInput?.focus();
        });

        document.getElementById('nip-btn-confirm')?.addEventListener('click', () => {
            document.getElementById('nip-confirm-overlay')?.classList.remove('active');
            this.submitIntercepted = true;

            const form = window._nipPendingFormWrapper || this.nipInput?.closest('form');
            if (!form) return;

            const submitForm = () => {
                window.onbeforeunload = null;
                form.submit();
            };

            if (window.crmValidateCompanyNameBeforeSubmit) {
                window.crmValidateCompanyNameBeforeSubmit(form, submitForm);
            } else {
                submitForm();
            }
        });
    }

    initCounter() {
        const input = this.nipInput;
        if (!input || input.dataset.nipCountInit) return;
        input.dataset.nipCountInit = '1';

        const counterWrap = document.createElement('div');
        counterWrap.className = 'nip-counter-wrap';
        counterWrap.innerHTML = '<span class="nip-counter empty" id="nip-char-counter">0 / 10 znaków</span>'
            + '<span id="nip-counter-hint" style="color:#94a3b8; margin-left:6px;"></span>';

        input.parentNode.insertBefore(counterWrap, input.nextSibling);
        this.updateCounter();
    }

    updateCounter() {
        const input = this.nipInput;
        const counter = document.getElementById('nip-char-counter');
        const hint = document.getElementById('nip-counter-hint');
        if (!input || !counter || !hint) return;

        const v = input.value.trim();
        const len = v.length;
        counter.textContent = `${len} / 10 znaków`;

        if (len === 0) {
            counter.className = 'nip-counter empty';
            hint.textContent = '— zostaw puste dla działalności nierejestrowanej';
        } else if (len === 10) {
            counter.className = 'nip-counter ok';
            hint.textContent = '✓ poprawna długość';
        } else {
            counter.className = 'nip-counter bad';
            hint.textContent = len < 10 ? `✗ za krótki (${10 - len} brakuje)` : `✗ za długi (o ${len - 10} za dużo)`;
        }
    }

    bindEvents() {
        this.handleInput = () => this.updateCounter();
        this.nipInput?.addEventListener('input', this.handleInput);

        this.handleSubmit = (e) => {
            const input = this.nipInput;
            if (!input || this.submitIntercepted) return;

            const form = input.closest('form');
            if (e.target === form) {
                const v = input.value.trim();
                const len = v.length;
                if (len !== 0 && len !== 10) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    this.showConfirmModal(input, v, len, form);
                }
            }
        };

        document.addEventListener('submit', this.handleSubmit, true);
    }

    unbindEvents() {
        if (this.handleInput && this.nipInput) {
            this.nipInput.removeEventListener('input', this.handleInput);
        }
        if (this.handleSubmit) {
            document.removeEventListener('submit', this.handleSubmit, true);
        }
    }

    showConfirmModal(nipInput, v, len, form) {
        this.initModal();
        const overlay = document.getElementById('nip-confirm-overlay');
        const confirmText = document.getElementById('nip-confirm-text');
        if (!overlay || !confirmText) return;

        const escapedV = v.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        confirmText.innerHTML = `Wpisany NIP <strong>${escapedV}</strong> ma <strong>${len} znaków</strong> zamiast 10.<br><br>Czy na pewno chcesz go zapisać?`;

        window._nipPendingFormWrapper = form;
        overlay.dataset.pendingFormId = form.id;
        overlay.classList.add('active');
    }
}
