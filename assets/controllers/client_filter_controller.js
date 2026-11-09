import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        window.updateClientFilterParam = (param, value) => this.navigate(param, value);
    }

    disconnect() {
        if (window.updateClientFilterParam) delete window.updateClientFilterParam;
    }

    updateParam(event) {
        const param = event.params?.param || event.currentTarget?.dataset?.clientFilterParamParam;
        let value = event.currentTarget.value;
        if (event.currentTarget.type === 'checkbox') {
            value = event.currentTarget.checked ? (event.currentTarget.value || '1') : '';
        }
        this.navigate(param, value);
    }

    navigate(param, value) {
        const url = new URL(window.location.href);
        url.searchParams.delete('page');
        if (value === undefined || value === null || value === '') {
            url.searchParams.delete(param);
        } else {
            url.searchParams.set(param, value);
        }
        window.location.href = url.toString();
    }
}
