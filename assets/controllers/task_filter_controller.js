import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        if (!window.location.pathname.match(/\/admin\/task\/?$/)) return;
        if (document.getElementById('my-tasks-toggle')) return;

        const headerActions = document.querySelector('.global-actions');
        if (!headerActions) return;

        const url = new URL(window.location.href);
        const myTasks = url.searchParams.get('myTasks');
        const isChecked = myTasks === null || myTasks === '1';

        const container = document.createElement('div');
        container.id = 'my-tasks-toggle';
        container.style.cssText = 'display:inline-flex;align-items:center;gap:8px;margin-right:16px;padding:6px 14px;background:#f0f4f8;border-radius:6px;font-size:14px;font-weight:500;cursor:pointer;user-select:none;border:1px solid #d1d5db;';

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.checked = isChecked;
        checkbox.style.cssText = 'width:16px;height:16px;pointer-events:none;accent-color:#1e3a5f;';

        const label = document.createElement('span');
        label.textContent = 'Przypisane do mnie';
        label.style.cssText = 'color:#374151;';

        container.appendChild(checkbox);
        container.appendChild(label);
        headerActions.insertBefore(container, headerActions.firstChild);

        container.addEventListener('click', () => {
            checkbox.checked = !checkbox.checked;
            const newUrl = new URL(window.location.href);
            newUrl.searchParams.set('myTasks', checkbox.checked ? '1' : '0');
            newUrl.searchParams.delete('page');
            window.location.href = newUrl.toString();
        });
    }
}
