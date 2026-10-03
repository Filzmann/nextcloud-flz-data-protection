import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../..', import.meta.url));
const source = readFileSync(`${root}/js/risk-scope-authorization.js`, 'utf8');
const fields = ['scopeId', 'enabled', 'policyRevision', 'authorizationReference', 'effectiveAt', 'expiresAt', 'dpoConfirmed', 'expectedRevision'];
const elements = Object.fromEntries(fields.map(name => [name, { value: '', checked: false, disabled: false }]));
elements.scopeId.value = 'adroom.secretariat.foreign-booking-intervention';
const listeners = {};
let valid = false;
let validityReports = 0;
const form = {
    elements,
    addEventListener: (name, callback) => { listeners[name] = callback; },
    checkValidity: () => valid,
    reportValidity: () => { validityReports += 1; },
};
const status = { textContent: '', role: '', setAttribute: (_name, value) => { status.role = value; } };
const document = {
    getElementById: id => id.endsWith('-form') ? form : (id.endsWith('-status') ? status : null),
};
const FormData = class {
    constructor(value) { this.form = value; }
    get(name) { const field = this.form.elements[name]; return field.checked ? 'on' : field.value; }
};
const calls = [];
const missing = {
    contractVersion: '1.0', performanceMonitoringProhibited: true,
    scopes: { 'adroom.secretariat.foreign-booking-intervention': { consumerAppId: 'adroom', authorized: false, status: 'configuration_missing', configuration: null } },
};
const authorized = {
    contractVersion: '1.0', performanceMonitoringProhibited: true,
    scopes: { 'adroom.secretariat.foreign-booking-intervention': {
        consumerAppId: 'adroom', authorized: true, status: 'authorized',
        configuration: { revision: 1, scopeId: 'adroom.secretariat.foreign-booking-intervention', enabled: true, policyRevision: 'SYNTH-REV-1', authorizationReference: 'SYNTH-EVIDENCE-1', effectiveAt: '2026-09-29T10:00:00+00:00', expiresAt: '2026-10-29T10:00:00+00:00', dpoConfirmed: true },
    } },
};
const expired = {
    contractVersion: '1.0', performanceMonitoringProhibited: true,
    scopes: { 'adroom.secretariat.foreign-booking-intervention': { consumerAppId: 'adroom', authorized: false, status: 'expired', configuration: authorized.scopes['adroom.secretariat.foreign-booking-intervention'].configuration } },
};
let responseState = missing;
const fetch = async (url, options = {}) => {
    calls.push([url, options]);
    return { ok: true, json: async () => options.method === 'PUT' ? { status: authorized } : { status: responseState } };
};
const OC = { requestToken: 'synthetic-token', generateUrl: path => path };

vm.runInNewContext(source, { document, FormData, fetch, OC, Date, Error, String, Number });
await new Promise(resolve => setTimeout(resolve, 0));
if (calls[0]?.[1]?.headers?.requesttoken !== OC.requestToken || elements.expectedRevision.value !== '0') throw new Error('Fehlender Scope-Status wird nicht sitzungsgebunden geladen.');
if (!status.textContent.includes('nicht konfiguriert') || status.role !== 'status') throw new Error('Ein fehlender Scope wird nicht sicher und zugänglich angezeigt.');

responseState = expired;
listeners.change({ target: elements.scopeId });
await new Promise(resolve => setTimeout(resolve, 0));
if (!status.textContent.includes('abgelaufen') || status.role !== 'status') throw new Error('Ein abgelaufener Scope wird nicht sicher und zugänglich angezeigt.');

await listeners.submit({ preventDefault() {} });
if (calls.length !== 2 || validityReports !== 1 || status.role !== 'alert') throw new Error('Unvollständige Scope-Eingaben dürfen nicht gespeichert werden.');

elements.scopeId.value = 'adroom.secretariat.foreign-booking-intervention';
elements.enabled.checked = true;
elements.policyRevision.value = 'SYNTH-REV-1';
elements.authorizationReference.value = 'SYNTH-EVIDENCE-1';
elements.effectiveAt.value = '2026-09-29T10:00';
elements.expiresAt.value = '2026-10-29T10:00';
elements.dpoConfirmed.checked = true;
elements.expectedRevision.value = '0';
valid = true;

await listeners.submit({ preventDefault() {} });
const update = calls[2];
const body = JSON.parse(update?.[1]?.body || '{}');
if (update?.[1]?.method !== 'PUT' || update?.[1]?.headers?.requesttoken !== OC.requestToken) throw new Error('Die Scope-Mutation ist nicht CSRF-geschützt.');
if (body.configuration?.scopeId !== elements.scopeId.value || body.configuration?.dpoConfirmed !== true || body.configuration?.expectedRevision !== 0) throw new Error('Die geschlossene Scope-Konfiguration wird nicht vollständig gespeichert.');
if (!status.textContent.includes('aktiv') || status.role !== 'status') throw new Error('Der erfolgreiche Scope-Status wird nicht zugänglich angezeigt.');

console.log('Risk scope authorization UI smoke passed.');
