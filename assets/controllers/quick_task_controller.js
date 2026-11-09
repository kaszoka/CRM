import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        formId: { type: String, default: 'quick-task-form' }
    };

    submit(event) {
        event.preventDefault();
        const clientForm = document.querySelector('form[name="Client"]');
        const taskForm = document.getElementById(this.formIdValue) || this.element.closest('form');
        if (!clientForm || !taskForm) return;

        const titleInput = taskForm.querySelector('[name$="[title]"]');
        if (titleInput && titleInput.value.trim() === '') {
            titleInput.focus();
            titleInput.classList.add('is-invalid');
            let fb = titleInput.nextElementSibling;
            if (!fb || !fb.classList.contains('invalid-feedback')) {
                fb = document.createElement('div');
                fb.className = 'invalid-feedback';
                fb.textContent = 'Tytuł zadania jest wymagany.';
                titleInput.parentNode.insertBefore(fb, titleInput.nextSibling);
            }
            return;
        }
        if (titleInput) {
            titleInput.classList.remove('is-invalid');
        }

        const submitTaskForm = () => {
            const existing = taskForm.querySelectorAll('input[data-client-mirror]');
            existing.forEach(el => el.remove());

            const elements = clientForm.elements;
            for (let i = 0; i < elements.length; i++) {
                const el = elements[i];
                if (!el.name || el.disabled) continue;
                if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) continue;
                if (el.type === 'submit' || el.type === 'button') continue;

                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = el.name;
                hidden.value = el.value;
                hidden.dataset.clientMirror = '1';
                taskForm.appendChild(hidden);
            }

            window.onbeforeunload = null;
            taskForm.submit();
        };

        if (window.crmValidateCompanyNameBeforeSubmit) {
            window.crmValidateCompanyNameBeforeSubmit(clientForm, submitTaskForm);
        } else {
            submitTaskForm();
        }
    }
}
