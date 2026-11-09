import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        mode: { type: String, default: 'new' }
    };

    saveAndGoTasks(event) {
        event.preventDefault();
        const form = document.querySelector('form[name="Client"]');
        if (!form) return;

        const existing = form.querySelector('input[data-ea-btn-inject]');
        if (existing) existing.remove();

        const fieldName = this.modeValue === 'edit' ? 'ea[editForm][btn]' : 'ea[newForm][btn]';
        const h = document.createElement('input');
        h.type = 'hidden';
        h.name = fieldName;
        h.value = 'saveAndGoTasks';
        h.dataset.eaBtnInject = '1';
        form.appendChild(h);

        const nipInput = document.getElementById('Client_nip');
        if (nipInput) {
            const v = nipInput.value.trim();
            const len = v.length;
            if (len !== 0 && len !== 10) {
                if (window.showModal) {
                    window.showModal(nipInput, v, len, form);
                    return;
                }
            }
        }

        const submitForm = () => {
            window.onbeforeunload = null;
            form.submit();
        };

        if (window.crmValidateCompanyNameBeforeSubmit) {
            window.crmValidateCompanyNameBeforeSubmit(form, submitForm);
        } else {
            submitForm();
        }
    }
}
