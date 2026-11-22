import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../..', import.meta.url));
const sourcePath = `${root}/js/admin.js`;
const source = readFileSync(sourcePath, 'utf8');

const listeners = {};
const selects = [
    {
        disabled: false,
        dataset: { permission: 'retention.review' },
        selectedOptions: [{ value: 'Datenschutzbeauftragte' }, { value: 'Org-Datenschutz' }],
    },
    {
        disabled: true,
        dataset: { permission: 'risk-scope.configure' },
        selectedOptions: [{ value: 'Datenschutzbeauftragte' }],
    },
];
const form = {
    addEventListener(name, callback) { listeners[name] = callback; },
    querySelectorAll(selector) { return selector === 'select[data-permission]' ? selects : []; },
};
const notice = {
    textContent: '', hidden: true, role: '',
    setAttribute(name, value) { if (name === 'role') this.role = value; },
};
const document = {
    getElementById(id) {
        if (id === 'data-protection-permission-settings-form') return form;
        if (id === 'data-protection-admin-notice') return notice;
        return null;
    },
};
const calls = [];
const fetch = async (url, options) => {
    calls.push([url, options]);
    return { ok: true, json: async () => ({ assignments: {} }) };
};
const OC = { requestToken: 'synthetic-token', generateUrl: path => path };

vm.runInNewContext(source, { document, fetch, OC, URLSearchParams, Error }, { filename: sourcePath });
if (typeof listeners.submit !== 'function') throw new Error('Das sichtbare Berechtigungsformular wird nicht initialisiert.');

await listeners.submit({ preventDefault() {} });
const [url, options] = calls[0] ?? [];
if (url !== '/apps/flz_data_protection/api/v1/retention-settings' || options?.method !== 'POST') {
    throw new Error('Die Rechtezuordnung verwendet nicht den geschlossenen Mutationspfad.');
}
if (options?.headers?.requesttoken !== OC.requestToken) throw new Error('Die Rechtezuordnung sendet kein CSRF-Token.');
const body = options.body;
if (!(body instanceof URLSearchParams)) throw new Error('Die Rechtezuordnung wird nicht als geschlossene Formularkodierung gesendet.');
if (body.getAll('assignments[retention.review][]').join(',') !== 'Datenschutzbeauftragte,Org-Datenschutz') {
    throw new Error('Die ausgewählten Rollen werden nicht dem einzelnen Recht zugeordnet.');
}
if (body.has('assignments[risk-scope.configure][]')) {
    throw new Error('Ein nur lesbar dargestelltes Fremdrecht wird versehentlich mitgespeichert.');
}
if (notice.hidden || notice.role !== 'status' || notice.textContent !== 'Gespeichert.') {
    throw new Error('Erfolgreiches Speichern wird nicht zugänglich bestätigt.');
}

console.log('Permission settings UI smoke passed.');
