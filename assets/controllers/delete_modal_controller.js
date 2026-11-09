import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['modal', 'text', 'confirm'];
    static values = { formId: String };

    connect() {
        window.openDeleteModal = (formId, name) => this.openModal(formId, name);
        window.closeDeleteModal = () => this.closeModal();
    }

    disconnect() {
        if (window.openDeleteModal) delete window.openDeleteModal;
        if (window.closeDeleteModal) delete window.closeDeleteModal;
    }

    open(event) {
        const formId = event.params?.formId || event.currentTarget?.dataset?.deleteModalFormIdParam;
        const name = event.params?.name || event.currentTarget?.dataset?.deleteModalNameParam || '';
        this.openModal(formId, name);
    }

    openModal(formId, name) {
        this.formIdValue = formId;
        const modal = this.hasModalTarget ? this.modalTarget : this.element;
        const text = this.hasTextTarget ? this.textTarget : modal.querySelector('#delete-modal-text');
        if (text) {
            text.textContent = `Na pewno usunąć "${name}"? Tej operacji nie można cofnąć.`;
        }
        modal.style.display = 'flex';
    }

    close() {
        this.closeModal();
    }

    closeModal() {
        const modal = this.hasModalTarget ? this.modalTarget : this.element;
        modal.style.display = 'none';
        this.formIdValue = '';
    }

    confirm() {
        if (this.formIdValue) {
            const form = document.getElementById(this.formIdValue);
            if (form) {
                form.submit();
            }
        }
    }

    backdrop(event) {
        const modal = this.hasModalTarget ? this.modalTarget : this.element;
        if (event.target === modal) {
            this.closeModal();
        }
    }
}
