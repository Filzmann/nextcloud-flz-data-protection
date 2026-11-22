import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';

const rootPath = fileURLToPath(new URL('../..', import.meta.url));
const sourcePath = `${rootPath}/js/admin-access.js`;
const source = readFileSync(sourcePath, 'utf8');

const runScenario = async ({ canManage, withForm, withHistory }) => {
    const calls = [];
    const formListeners = {};
    const historyListeners = {};
    let buttonsCreated = 0;
    const root = { dataset: { canManage: canManage ? 'true' : 'false' } };
    const form = withForm ? {
        elements: { enabled: { checked: true } },
        addEventListener(name, callback) { formListeners[name] = callback; },
    } : null;
    const history = withHistory ? {
        rows: [],
        addEventListener(name, callback) { historyListeners[name] = callback; },
        replaceChildren() { this.rows = []; },
        append(row) { this.rows.push(row); },
    } : null;
    const status = { textContent: '', role: '', setAttribute(name, value) { if (name === 'role') this.role = value; } };
    const document = {
        getElementById(id) {
            if (id === 'data-protection-full-access') return root;
            if (id === 'data-protection-full-access-form') return form;
            if (id === 'data-protection-full-access-history') return history;
            if (id === 'data-protection-full-access-status') return status;
            return null;
        },
        createElement(tag) {
            if (tag === 'button') buttonsCreated += 1;
            return {
                tag, children: [], dataset: {}, textContent: '', disabled: false,
                append(child) { this.children.push(child); },
                closest() { return null; },
            };
        },
    };
    const fetch = async (url, options = {}) => {
        calls.push([url, options]);
        if (options.method === 'POST') return { ok: true, json: async () => ({ grant: {} }) };
        return { ok: true, json: async () => ({ history: [{
            targetUid: 'synthetic-admin', grantedBy: 'synthetic-dpo',
            startsAt: '2020-01-01T00:00:00Z', endsAt: '2099-01-01T00:00:00Z', revokedAt: null,
        }] }) };
    };
    const FormData = class {
        get(name) {
            return { enabled: 'on', targetUid: 'synthetic-admin', durationMinutes: '60' }[name] ?? null;
        }
    };
    const OC = { requestToken: 'synthetic-token', generateUrl: path => path };
    vm.runInNewContext(source, { document, fetch, FormData, OC, Date, JSON, Number, String, encodeURIComponent, Error }, { filename: sourcePath });
    await new Promise(resolve => setTimeout(resolve, 0));
    return { calls, formListeners, historyListeners, buttonsCreated, status };
};

const manager = await runScenario({ canManage: true, withForm: true, withHistory: false });
if (typeof manager.formListeners.submit !== 'function') throw new Error('Das getrennte Verwaltungsrecht initialisiert die Freigabemutation nicht.');
if (manager.calls.length !== 0) throw new Error('Das Verwaltungsrecht versucht ohne Leserecht die Freigabehistorie abzurufen.');
await manager.formListeners.submit({ preventDefault() {} });
if (manager.calls[0]?.[1]?.method !== 'POST' || manager.calls[0]?.[1]?.headers?.requesttoken !== 'synthetic-token') {
    throw new Error('Das getrennte Verwaltungsrecht kann keine CSRF-geschützte Freigabe erteilen.');
}

const reader = await runScenario({ canManage: false, withForm: false, withHistory: true });
if (reader.calls[0]?.[1]?.method === 'POST' || reader.calls.length !== 1) throw new Error('Das reine Leserecht löst eine Mutation aus oder lädt die Historie nicht genau einmal.');
if (reader.buttonsCreated !== 0 || typeof reader.historyListeners.click === 'function') {
    throw new Error('Das reine Historienleserecht erhält eine widerrufende Bedienhandlung.');
}

console.log('Admin access permission split UI smoke passed.');
