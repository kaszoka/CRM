import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    ENDPOINT = '/admin/client/company-name-check';

    connect() {
        this.pendingContinue = null;
        this.lastCheckedName = '';
        this.activeWarningBox = null;
        this.activeAbortCtrl = null;
        this.isTyping = false;
        this.typingTimeout = null;

        this.initModal();
        this.bindEvents();

        window.crmValidateCompanyNameBeforeSubmit = (form, onValid) => {
            this.validateBeforeSubmit(form, onValid);
        };
    }

    disconnect() {
        this.unbindEvents();
        if (window.crmValidateCompanyNameBeforeSubmit) {
            delete window.crmValidateCompanyNameBeforeSubmit;
        }
    }

    get form() {
        return document.querySelector('form[name="Client"]');
    }

    get input() {
        return this.form?.querySelector('[name="Client[companyName]"], #Client_companyName');
    }

    getCurrentClientId() {
        const match = (document.body.id || '').match(/^ea-edit-[^-]+-(\d+)$/);
        if (match) return match[1];
        const params = new URLSearchParams(window.location.search);
        return params.get('entityId') || '';
    }

    initModal() {
        if (document.getElementById('company-name-check-overlay')) return;

        const html = `
            <div id="company-name-check-overlay" style="display:none;position:fixed;inset:0;z-index:100000;background:rgba(15,23,42,.62);align-items:center;justify-content:center;padding:20px;">
              <div style="width:min(620px,96vw);max-height:86vh;overflow:auto;background:#fff;border-radius:14px;box-shadow:0 24px 80px rgba(15,23,42,.35);border:1px solid #e2e8f0;">
                <div style="padding:18px 22px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:10px;">
                  <i class="fa fa-triangle-exclamation" style="color:#f59f00;font-size:20px;"></i>
                  <strong id="company-name-check-title" style="font-size:17px;color:#0f172a;">Sprawdzenie nazwy firmy</strong>
                </div>
                <div style="padding:18px 22px;">
                  <p id="company-name-check-message" style="margin:0 0 12px;color:#334155;line-height:1.45;"></p>
                  <div id="company-name-check-list" style="display:flex;flex-direction:column;gap:8px;"></div>
                </div>
                <div id="company-name-check-actions" style="padding:14px 22px;border-top:1px solid #e2e8f0;display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;">
                  <button type="button" id="company-name-check-cancel" class="btn btn-secondary">Popraw nazwę</button>
                  <button type="button" id="company-name-check-continue" class="btn btn-primary" style="display:none;">Zapisz mimo to</button>
                </div>
              </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', html);

        document.getElementById('company-name-check-cancel')?.addEventListener('click', () => {
            this.closeModal();
            this.input?.focus();
        });

        document.getElementById('company-name-check-continue')?.addEventListener('click', () => {
            const cb = this.pendingContinue;
            this.closeModal();
            if (cb) cb();
        });
    }

    closeModal() {
        const overlay = document.getElementById('company-name-check-overlay');
        if (overlay) overlay.style.display = 'none';
        this.pendingContinue = null;
    }

    bindEvents() {
        const inp = this.input;
        if (!inp) return;

        this.handleInput = () => {
            this.isTyping = true;
            clearTimeout(this.typingTimeout);
            this.typingTimeout = setTimeout(() => {
                this.isTyping = false;
                this.check(inp.value, false);
            }, 300);
        };

        this.handleBlur = () => {
            this.isTyping = false;
            this.check(inp.value, false);
        };

        inp.addEventListener('input', this.handleInput);
        inp.addEventListener('blur', this.handleBlur);
    }

    unbindEvents() {
        if (this.handleInput && this.input) {
            this.input.removeEventListener('input', this.handleInput);
        }
        if (this.handleBlur && this.input) {
            this.input.removeEventListener('blur', this.handleBlur);
        }
    }

    check(rawName, forSubmit, onDone) {
        const name = (rawName || '').trim().replace(/\s+/g, ' ');
        if (!name || name.length < 2) {
            this.hideWarning();
            if (onDone) onDone(null);
            return;
        }

        if (this.activeAbortCtrl) {
            this.activeAbortCtrl.abort();
        }
        this.activeAbortCtrl = new AbortController();

        const url = new URL(this.ENDPOINT, window.location.origin);
        url.searchParams.set('name', name);
        const cid = this.getCurrentClientId();
        if (cid) url.searchParams.set('excludeId', cid);

        fetch(url.toString(), {
            signal: this.activeAbortCtrl.signal,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            this.renderWarning(data);
            if (onDone) onDone(data);
        })
        .catch(err => {
            if (err.name !== 'AbortError' && onDone) onDone(null);
        });
    }

    renderWarning(data) {
        if (!data || !data.hasMatches) {
            this.hideWarning();
            return;
        }
        let box = this.activeWarningBox;
        if (!box) {
            box = document.createElement('div');
            box.id = 'company-name-warning-box';
            box.style.cssText = 'margin-top:6px;padding:8px 12px;background:#fffbeb;border:1px solid #fcd34d;border-radius:6px;font-size:13px;color:#92400e;display:flex;align-items:center;gap:8px;';
            this.input?.parentNode.insertBefore(box, this.input.nextSibling);
            this.activeWarningBox = box;
        }
        box.innerHTML = `<i class="fa fa-triangle-exclamation"></i> Istnieją firmy o podobnej lub identycznej nazwie (${data.matches?.length || 0})`;
    }

    hideWarning() {
        if (this.activeWarningBox) {
            this.activeWarningBox.remove();
            this.activeWarningBox = null;
        }
    }

    validateBeforeSubmit(form, onValid) {
        const inp = this.input;
        if (!inp) {
            onValid();
            return;
        }
        this.check(inp.value, true, (data) => {
            if (data && data.hasMatches) {
                this.showModalDialog(data, onValid);
            } else {
                onValid();
            }
        });
    }

    showModalDialog(data, onContinue) {
        this.initModal();
        const overlay = document.getElementById('company-name-check-overlay');
        const msg = document.getElementById('company-name-check-message');
        const list = document.getElementById('company-name-check-list');
        const continueBtn = document.getElementById('company-name-check-continue');

        if (!overlay || !msg || !list || !continueBtn) return;

        const hasExact = data.matches?.some(m => m.type === 'exact');
        msg.textContent = hasExact
            ? 'Firma o dokładnie takiej nazwie już istnieje w bazie. Czy na pewno chcesz zapisać?'
            : 'Firma o bardzo podobnej nazwie już istnieje w bazie.';

        list.innerHTML = (data.matches || []).map(m => `
            <div style="padding:6px 10px;background:#f8fafc;border-radius:6px;font-size:13px;border:1px solid #e2e8f0;">
                <strong>${m.companyName}</strong> ${m.nip ? `(NIP: ${m.nip})` : ''}
            </div>
        `).join('');

        continueBtn.style.display = 'inline-block';
        this.pendingContinue = onContinue;
        overlay.style.display = 'flex';
    }
}
