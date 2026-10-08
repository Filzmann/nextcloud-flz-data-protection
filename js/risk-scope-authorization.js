(function () {
    'use strict';

    const form = document.getElementById('data-protection-risk-scope-authorization-form');
    const statusElement = document.getElementById('data-protection-risk-scope-authorization-status');
    if (!form || !statusElement) return;

    const endpoint = '/api/v1/risk-scope-authorizations';
    const request = async (options = {}) => {
        const response = await fetch(OC.generateUrl('/apps/flz_data_protection' + endpoint), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', requesttoken: OC.requestToken },
            ...options,
        });
        const body = await response.json();
        if (!response.ok) throw new Error(body.message || 'Die Risikofunktionsfreigabe konnte nicht verarbeitet werden.');
        return body;
    };

    const localDate = value => {
        if (!value) return '';
        const date = new Date(value);
        const local = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
        return local.toISOString().slice(0, 16);
    };

    const statusText = state => ({
        authorized: 'aktiv',
        disabled: 'deaktiviert',
        configuration_missing: 'nicht konfiguriert und daher gesperrt',
        configuration_invalid: 'ungültig und daher gesperrt',
        not_yet_effective: 'noch nicht wirksam und daher gesperrt',
        expired: 'abgelaufen und daher gesperrt',
    }[state] || 'unbekannt und daher gesperrt');

    const selectedScope = state => state.scopes?.[form.elements.scopeId.value] || null;

    const render = state => {
        const scope = selectedScope(state);
        if (!scope || state.performanceMonitoringProhibited !== true) {
            throw new Error('Die unveränderliche Produktschutzgrenze konnte nicht bestätigt werden.');
        }
        const configuration = scope.configuration;
        form.elements.enabled.checked = configuration?.enabled === true;
        form.elements.policyRevision.value = configuration?.policyRevision || '';
        form.elements.authorizationReference.value = configuration?.authorizationReference || '';
        form.elements.effectiveAt.value = localDate(configuration?.effectiveAt);
        form.elements.expiresAt.value = localDate(configuration?.expiresAt);
        form.elements.dpoConfirmed.checked = configuration?.dpoConfirmed === true;
        form.elements.expectedRevision.value = String(configuration?.revision || 0);
        statusElement.setAttribute('role', 'status');
        statusElement.textContent = `Status: ${statusText(scope.status)}.`;
    };

    form.addEventListener('change', event => {
        if (event.target !== form.elements.scopeId) return;
        request().then(body => render(body.status)).catch(error => {
            statusElement.setAttribute('role', 'alert');
            statusElement.textContent = error.message;
        });
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (!form.checkValidity()) {
            form.reportValidity();
            statusElement.setAttribute('role', 'alert');
            statusElement.textContent = 'Die Freigabe ist unvollständig. Es wurde keine neue Revision gespeichert.';
            return;
        }
        try {
            const fields = new FormData(form);
            const configuration = {
                scopeId: String(fields.get('scopeId') || ''),
                enabled: fields.get('enabled') === 'on',
                policyRevision: String(fields.get('policyRevision') || ''),
                authorizationReference: String(fields.get('authorizationReference') || ''),
                effectiveAt: new Date(String(fields.get('effectiveAt'))).toISOString(),
                expiresAt: new Date(String(fields.get('expiresAt'))).toISOString(),
                dpoConfirmed: fields.get('dpoConfirmed') === 'on',
                expectedRevision: Number(fields.get('expectedRevision')),
                performanceMonitoringProhibited: true,
            };
            render((await request({ method: 'PUT', body: JSON.stringify({ configuration }) })).status);
        } catch (error) {
            statusElement.setAttribute('role', 'alert');
            statusElement.textContent = error.message;
        }
    });

    void request().then(body => render(body.status)).catch(error => {
        statusElement.setAttribute('role', 'alert');
        statusElement.textContent = 'Die Risikofunktionsfreigabe konnte nicht geladen werden.';
    });
}());
