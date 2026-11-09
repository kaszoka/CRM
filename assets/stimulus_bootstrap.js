import { startStimulusApp } from '@symfony/stimulus-bundle';
import DeleteModalController from './controllers/delete_modal_controller.js';
import ClientFilterController from './controllers/client_filter_controller.js';
import EmailExportController from './controllers/email_export_controller.js';
import ClientFormController from './controllers/client_form_controller.js';
import QuickTaskController from './controllers/quick_task_controller.js';
import TwoFactorController from './controllers/two_factor_controller.js';
import FormSelectFixController from './controllers/form_select_fix_controller.js';
import NipValidatorController from './controllers/nip_validator_controller.js';
import CompanyNameCheckController from './controllers/company_name_check_controller.js';
import RowClickController from './controllers/row_click_controller.js';
import TaskFilterController from './controllers/task_filter_controller.js';
import ToastController from './controllers/toast_controller.js';
import AutocompleteFixController from './controllers/autocomplete_fix_controller.js';
import IndustryCreateController from './controllers/industry_create_controller.js';

const app = startStimulusApp();

app.register('delete-modal', DeleteModalController);
app.register('client-filter', ClientFilterController);
app.register('email-export', EmailExportController);
app.register('client-form', ClientFormController);
app.register('quick-task', QuickTaskController);
app.register('two-factor', TwoFactorController);
app.register('form-select-fix', FormSelectFixController);
app.register('nip-validator', NipValidatorController);
app.register('company-name-check', CompanyNameCheckController);
app.register('row-click', RowClickController);
app.register('task-filter', TaskFilterController);
app.register('toast', ToastController);
app.register('autocomplete-fix', AutocompleteFixController);
app.register('industry-create', IndustryCreateController);

export { app };
