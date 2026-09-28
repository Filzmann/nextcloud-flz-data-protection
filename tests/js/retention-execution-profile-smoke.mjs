import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../..', import.meta.url));
const source = readFileSync(`${root}/js/retention-execution-profile.js`, 'utf8');
const fieldNames = [
    'profileId', 'profileRevision', 'legalEvidenceReference', 'scopeReference', 'accountCategories',
    'purposeReference', 'necessityAssessmentReference', 'impactAssessmentReference', 'safeguardsReference',
    'backupResponsibleParty', 'backupScope', 'backupEvidenceReference', 'restoreTestReference',
    'effectiveAt', 'legalReviewDueAt', 'backupEvidenceAt', 'backupReviewDueAt', 'restoreTestedAt',
    'backupRegularDays', 'backupBufferDays', 'allAccountsEmployeesConfirmed', 'dpoConfirmed', 'expectedRevision',
];
const elements = Object.fromEntries(fieldNames.map((name) => [name, { value: '', checked: false }]));
const listeners = {};
const form = { elements, addEventListener: (name, callback) => { listeners[name] = callback; } };
const statusElement = { textContent: '', role: '', setAttribute: (_name, value) => { statusElement.role = value; } };
const document = { getElementById: id => id.endsWith('-form') ? form : (id.endsWith('-status') ? statusElement : null) };
const FormData = class { constructor(value) { this.form = value; } get(name) { const field = this.form.elements[name]; return field.checked ? 'on' : field.value; } };
const calls = [];
const state = {
    action: 'REVIEW', configurationValid: true, executionAvailable: false, performanceMonitoringProhibited: true, blockers: [],
    configuration: {
        revision: 1, profileId: 'employment_collective_agreement_de', profileRevision: 'SYNTH-REV-1', legalEvidenceReference: 'SYNTH-EVIDENCE-1',
        scopeReference: 'Synthetic scope', accountCategories: 'Synthetic employees', purposeReference: 'Synthetic security purpose',
        necessityAssessmentReference: '', impactAssessmentReference: '', safeguardsReference: 'Synthetic safeguards',
        backupResponsibleParty: 'Synthetic operations', backupScope: 'Synthetic database backup', backupEvidenceReference: 'SYNTH-BACKUP-1', restoreTestReference: 'SYNTH-RESTORE-1',
        effectiveAt: '2026-10-01T10:00:00+00:00', legalReviewDueAt: '2027-10-01T10:00:00+00:00', backupEvidenceAt: '2026-09-30T10:00:00+00:00',
        backupReviewDueAt: '2027-03-30T10:00:00+00:00', restoreTestedAt: '2026-09-30T12:00:00+00:00', backupRegularDays: 30, backupBufferDays: 5,
        allAccountsEmployeesConfirmed: true, dpoConfirmed: true,
    },
};
const fetch = async (url, options = {}) => {
    calls.push([url, options]);
    return { ok: true, json: async () => options.method === 'PUT' ? { status: state } : { status: state, history: [] } };
};
const OC = { requestToken: 'synthetic-token', generateUrl: path => path };

vm.runInNewContext(source, { document, FormData, fetch, OC, Date, Error, String, Number });
await new Promise(resolve => setTimeout(resolve, 0));
if (calls[0]?.[1]?.headers?.requesttoken !== OC.requestToken || elements.expectedRevision.value !== '1') throw new Error('Profilstatus wird nicht sitzungsgebunden geladen und revisionsgetreu dargestellt.');

await listeners.submit({ preventDefault() {} });
const update = calls[1];
const body = JSON.parse(update?.[1]?.body || '{}');
if (update?.[1]?.method !== 'PUT' || update?.[1]?.headers?.requesttoken !== OC.requestToken) throw new Error('Profilmutation ist nicht CSRF-geschützt.');
if (body.configuration?.profileId !== 'employment_collective_agreement_de'
    || body.configuration?.expectedRevision !== 1
    || body.configuration?.performanceMonitoringProhibited !== true) {
    throw new Error('Geschlossenes Profil, Revision oder unveränderliche Schutzgrenze gehen bei der Mutation verloren.');
}
if (statusElement.role !== 'status' || !statusElement.textContent.includes('REVIEW-only')) throw new Error('Der erfolgreiche Status behauptet nicht wahrheitsgemäß REVIEW-only.');

console.log('Retention execution profile UI smoke passed.');
