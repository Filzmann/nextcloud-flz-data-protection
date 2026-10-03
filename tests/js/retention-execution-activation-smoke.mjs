import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../..', import.meta.url));
const source = readFileSync(`${root}/js/retention-execution-activation.js`, 'utf8');
const names = ['enabled', 'backupRegularDays', 'backupBufferDays', 'backupVerifiedAt', 'restoreVerifiedAt', 'verificationDueAt', 'expectedRevision'];
const elements = Object.fromEntries(names.map(name => [name, { value: '', checked: false, required: false, disabled: false }]));
const listeners = {};
const form = {
    elements,
    addEventListener: (name, callback) => { listeners[name] = callback; },
    checkValidity: () => true,
    reportValidity: () => {},
};
const statusElement = { textContent: '', role: '', setAttribute: (_name, value) => { statusElement.role = value; } };
const document = { getElementById: id => id.endsWith('-form') ? form : (id.endsWith('-status') ? statusElement : null) };
const FormData = class { constructor(value) { this.form = value; } get(name) { const field = this.form.elements[name]; return field.checked ? 'on' : field.value; } };
const calls = [];
const initialState = {
    action: 'REVIEW', setupRequired: true, configurationValid: false, executionAvailable: false,
    performanceMonitoringProhibited: true, blockers: ['activation_missing'],
    recommendedPolicyIds: ['adroom:room_booking_delete', 'adroom:temporary_admin_access_history_delete', 'filzmann_data_protection:temporary_admin_access_history_delete'],
    configuration: null,
};
const activeState = {
    ...initialState,
    action: 'DELETE', setupRequired: false, configurationValid: true, executionAvailable: true, blockers: [],
    configuration: {
        revision: 1, enabled: true, approvedPolicyIds: initialState.recommendedPolicyIds,
        backupRegularDays: 365, backupBufferDays: 5,
        backupVerifiedAt: '2026-10-01T09:00:00+00:00', restoreVerifiedAt: '2026-10-01T11:00:00+00:00',
        verificationDueAt: '2027-10-01T00:00:00+00:00', changedBy: 'operator', createdAt: '2026-10-02T10:00:00+00:00',
    },
};
const fetch = async (url, options = {}) => {
    calls.push([url, options]);
    return { ok: true, json: async () => options.method === 'PUT' ? { status: activeState } : { status: initialState, history: [] } };
};
const OC = { requestToken: 'synthetic-token', generateUrl: path => path };

vm.runInNewContext(source, { document, FormData, fetch, OC, Date, Error, String, Number });
await new Promise(resolve => setTimeout(resolve, 0));
if (!calls[0]?.[0]?.includes('/api/v2/retention-execution-activation')) throw new Error('Die technische Aktivierung lädt nicht über ihren getrennten API-Pfad.');
if (elements.enabled.checked || elements.expectedRevision.value !== '0') throw new Error('Eine frische Installation wird nicht sichtbar deaktiviert dargestellt.');
if (!statusElement.textContent.includes('deaktiviert')) throw new Error('Der sichere Initialzustand ist für den Betreiber nicht verständlich.');

elements.enabled.checked = true;
elements.backupRegularDays.value = '365';
elements.backupBufferDays.value = '5';
elements.backupVerifiedAt.value = '2026-10-01T09:00';
elements.restoreVerifiedAt.value = '2026-10-01T11:00';
elements.verificationDueAt.value = '2027-10-01T00:00';
await listeners.submit({ preventDefault() {} });

const update = calls[1];
const body = JSON.parse(update?.[1]?.body || '{}');
if (update?.[1]?.method !== 'PUT' || update?.[1]?.headers?.requesttoken !== OC.requestToken) throw new Error('Die Aktivierung ist nicht CSRF-geschützt.');
if (body.configuration?.enabled !== true || body.configuration?.backupRegularDays !== 365 || body.configuration?.expectedRevision !== 0) {
    throw new Error('Technische Aktivierungswerte oder Revisionsanker gehen bei der Mutation verloren.');
}
for (const forbidden of ['profileId', 'legalEvidenceReference', 'authorizationReference', 'dpoConfirmed']) {
    if (Object.hasOwn(body.configuration || {}, forbidden)) throw new Error(`Rechts-/DPO-Feld wird weiterhin als Aktivierungseingabe gesendet: ${forbidden}`);
}
if (!statusElement.textContent.includes('aktiv')) throw new Error('Der erfolgreiche technische Ausführungsstatus bleibt unsichtbar.');

console.log('Retention execution activation UI smoke passed.');
