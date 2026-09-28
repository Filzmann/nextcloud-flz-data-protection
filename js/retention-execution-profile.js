(function () {
    'use strict';

    const form = document.getElementById('data-protection-retention-execution-profile-form');
    const statusElement = document.getElementById('data-protection-retention-execution-profile-status');
    if (!form || !statusElement) return;

    const endpoint = '/api/v1/retention-execution-profile';
    const dateFields = ['effectiveAt', 'legalReviewDueAt', 'backupEvidenceAt', 'backupReviewDueAt', 'restoreTestedAt'];
    const textFields = [
        'profileId', 'profileRevision', 'legalEvidenceReference', 'scopeReference', 'accountCategories',
        'purposeReference', 'necessityAssessmentReference', 'impactAssessmentReference', 'safeguardsReference',
        'backupResponsibleParty', 'backupScope', 'backupEvidenceReference', 'restoreTestReference',
    ];

    const request = async (options = {}) => {
        const response = await fetch(OC.generateUrl('/apps/filzmann_data_protection' + endpoint), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', requesttoken: OC.requestToken },
            ...options,
        });
        const body = await response.json();
        if (!response.ok) throw new Error(body.message || 'Profilanfrage fehlgeschlagen.');
        return body;
    };

    const localDate = value => {
        if (!value) return '';
        const date = new Date(value);
        const local = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
        return local.toISOString().slice(0, 16);
    };

    const applyProfileRequirements = () => {
        const collectiveAgreement = form.elements.profileId.value === 'employment_collective_agreement_de';
        const legitimateInterest = form.elements.profileId.value === 'legitimate_interest_it_security_de';
        form.elements.allAccountsEmployeesConfirmed.required = collectiveAgreement;
        form.elements.necessityAssessmentReference.required = legitimateInterest;
        form.elements.impactAssessmentReference.required = legitimateInterest;
    };

    const render = state => {
        const configuration = state.configuration;
        if (configuration) {
            for (const field of textFields) form.elements[field].value = configuration[field] || '';
            for (const field of dateFields) form.elements[field].value = localDate(configuration[field]);
            form.elements.backupRegularDays.value = String(configuration.backupRegularDays);
            form.elements.backupBufferDays.value = String(configuration.backupBufferDays);
            form.elements.allAccountsEmployeesConfirmed.checked = configuration.allAccountsEmployeesConfirmed === true;
            form.elements.dpoConfirmed.checked = configuration.dpoConfirmed === true;
            form.elements.expectedRevision.value = String(configuration.revision);
        } else {
            form.elements.expectedRevision.value = '0';
        }
        applyProfileRequirements();
        statusElement.setAttribute('role', 'status');
        statusElement.textContent = state.setupRequired === true
            ? 'Ersteinrichtung erforderlich: Es wurde noch keine Profilrevision gespeichert. Retention bleibt REVIEW-only; eine Ausführung ist nicht verfügbar.'
            : state.configurationValid
            ? 'Profilnachweise sind aktuell. Die Retention bleibt REVIEW-only; eine Ausführung ist nicht verfügbar.'
            : 'Profilstatus REVIEW: ' + state.blockers.join(', ') + '. Eine Ausführung ist nicht verfügbar.';
        if (state.performanceMonitoringProhibited !== true || state.executionAvailable !== false) {
            throw new Error('Unveränderliche Produktschutzgrenze wurde verletzt.');
        }
    };

    form.addEventListener('change', applyProfileRequirements);

    form.addEventListener('submit', async event => {
        event.preventDefault();
        applyProfileRequirements();
        if (!form.checkValidity()) {
            form.reportValidity();
            statusElement.setAttribute('role', 'alert');
            statusElement.textContent = 'Die Ersteinrichtung ist unvollständig. Es wurde keine Profilrevision gespeichert.';
            return;
        }
        try {
            const fields = new FormData(form);
            const configuration = {};
            for (const field of textFields) configuration[field] = String(fields.get(field) || '');
            for (const field of dateFields) configuration[field] = new Date(String(fields.get(field))).toISOString();
            configuration.backupRegularDays = Number(fields.get('backupRegularDays'));
            configuration.backupBufferDays = Number(fields.get('backupBufferDays'));
            configuration.allAccountsEmployeesConfirmed = fields.get('allAccountsEmployeesConfirmed') === 'on';
            configuration.dpoConfirmed = fields.get('dpoConfirmed') === 'on';
            configuration.expectedRevision = Number(fields.get('expectedRevision'));
            configuration.performanceMonitoringProhibited = true;
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
