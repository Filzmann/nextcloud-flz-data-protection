(function () {
    'use strict';

    const form = document.getElementById('data-protection-retention-execution-activation-form');
    const statusElement = document.getElementById('data-protection-retention-execution-activation-status');
    if (!form || !statusElement) return;

    const endpoint = '/api/v2/retention-execution-activation';
    const technicalFields = ['backupRegularDays', 'backupBufferDays', 'backupVerifiedAt', 'restoreVerifiedAt', 'verificationDueAt'];
    const request = async (options = {}) => {
        const response = await fetch(OC.generateUrl('/apps/filzmann_data_protection' + endpoint), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', requesttoken: OC.requestToken },
            ...options,
        });
        const body = await response.json();
        if (!response.ok) throw new Error(body.message || 'Technische Aktivierung fehlgeschlagen.');
        return body;
    };
    const localDate = value => {
        if (!value) return '';
        const date = new Date(value);
        const local = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
        return local.toISOString().slice(0, 16);
    };
    const updateRequirements = () => {
        const enabled = form.elements.enabled.checked;
        for (const name of technicalFields) form.elements[name].required = enabled;
    };
    const render = state => {
        const configuration = state.configuration;
        form.elements.enabled.checked = configuration?.enabled === true;
        form.elements.expectedRevision.value = String(configuration?.revision || 0);
        if (configuration) {
            form.elements.backupRegularDays.value = String(configuration.backupRegularDays ?? 365);
            form.elements.backupBufferDays.value = String(configuration.backupBufferDays ?? 5);
            for (const name of ['backupVerifiedAt', 'restoreVerifiedAt', 'verificationDueAt']) {
                form.elements[name].value = localDate(configuration[name]);
            }
        }
        updateRequirements();
        statusElement.setAttribute('role', 'status');
        statusElement.textContent = state.executionAvailable === true
            ? 'Automatische Löschung ist technisch aktiv.'
            : 'Automatische Löschung ist deaktiviert. Es werden keine DELETE-Provider ausgeführt.';
    };

    form.addEventListener('change', updateRequirements);
    form.addEventListener('submit', async event => {
        event.preventDefault();
        updateRequirements();
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        try {
            const fields = new FormData(form);
            const enabled = fields.get('enabled') === 'on';
            const configuration = {
                enabled,
                expectedRevision: Number(fields.get('expectedRevision')),
            };
            if (enabled) {
                configuration.backupRegularDays = Number(fields.get('backupRegularDays'));
                configuration.backupBufferDays = Number(fields.get('backupBufferDays'));
                configuration.backupVerifiedAt = new Date(String(fields.get('backupVerifiedAt'))).toISOString();
                configuration.restoreVerifiedAt = new Date(String(fields.get('restoreVerifiedAt'))).toISOString();
                configuration.verificationDueAt = new Date(String(fields.get('verificationDueAt'))).toISOString();
            }
            render((await request({ method: 'PUT', body: JSON.stringify({ configuration }) })).status);
        } catch (error) {
            statusElement.setAttribute('role', 'alert');
            statusElement.textContent = error.message;
        }
    });

    void request().then(body => render(body.status)).catch(error => {
        statusElement.setAttribute('role', 'alert');
        statusElement.textContent = error.message;
    });
}());
