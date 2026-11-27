import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';

const rootPath = fileURLToPath(new URL('../..', import.meta.url));
const overlayPath = `${rootPath}/js/shared/overlay.js`;
const overlaySource = readFileSync(overlayPath, 'utf8');
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
    const overlay = { hidden: true, listeners: {}, addEventListener(name, callback) { (this.listeners[name] = this.listeners[name] || []).push(callback); }, setAttribute() {} };
    const trigger = { attributes: {}, focused: false, listeners: {}, addEventListener(name, callback) { (this.listeners[name] = this.listeners[name] || []).push(callback); }, setAttribute(name, value) { this.attributes[name] = String(value); }, getAttribute(name) { return this.attributes[name] ?? null; }, focus() { this.focused = true; } };
    const closeButton = { focused: false, listeners: {}, addEventListener(name, callback) { (this.listeners[name] = this.listeners[name] || []).push(callback); }, focus() { this.focused = true; } };
    const docListeners = {};
    const document = {
        getElementById(id) {
            if (id === 'data-protection-full-access') return root;
            if (id === 'data-protection-full-access-form') return form;
            if (id === 'data-protection-full-access-history') return history;
            if (id === 'data-protection-full-access-status') return status;
            if (id === 'data-protection-admin-access-overlay') return overlay;
            if (id === 'data-protection-admin-grant-warning') return trigger;
            if (id === 'data-protection-admin-access-overlay-close') return closeButton;
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
        addEventListener(name, callback) { (docListeners[name] = docListeners[name] || []).push(callback); },
        querySelectorAll: () => [],
    };
    const openOverlay = async () => {
        for (const callback of trigger.listeners.click || []) await callback({ target: trigger });
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
    const context = { window: {}, document, fetch, FormData, OC, Date, JSON, Number, String, Intl, Promise, encodeURIComponent, Error };
    vm.runInNewContext(overlaySource, context, { filename: overlayPath });
    vm.runInNewContext(source, context, { filename: sourcePath });
    await new Promise(resolve => setTimeout(resolve, 0));
    if (calls.length !== 0) throw new Error('Vor dem Öffnen des Overlays darf nichts geladen werden.');
    await openOverlay();
    if (overlay.hidden !== false || trigger.getAttribute('aria-expanded') !== 'true' || !closeButton.focused) throw new Error('Warn-Icon öffnet das Overlay nicht korrekt.');
    return { calls, formListeners, historyListeners, buttonsCreated, status, overlay, trigger, docListeners };
};

const manager = await runScenario({ canManage: true, withForm: true, withHistory: false });
if (typeof manager.formListeners.submit !== 'function') throw new Error('Das getrennte Verwaltungsrecht initialisiert die Freigabemutation nicht.');
if (manager.calls.length !== 0) throw new Error('Das Verwaltungsrecht versucht ohne Leserecht die Freigabehistorie abzurufen.');
await manager.formListeners.submit({ preventDefault() {} });
if (manager.calls[0]?.[1]?.method !== 'POST' || manager.calls[0]?.[1]?.headers?.requesttoken !== 'synthetic-token') {
    throw new Error('Das getrennte Verwaltungsrecht kann keine CSRF-geschützte Freigabe erteilen.');
}
for (const callback of manager.docListeners.keydown || []) await callback({ key: 'Escape' });
if (manager.overlay.hidden !== true || manager.trigger.getAttribute('aria-expanded') !== 'false') throw new Error('Escape schließt das Overlay nicht korrekt.');

const reader = await runScenario({ canManage: false, withForm: false, withHistory: true });
if (reader.calls[0]?.[1]?.method === 'POST' || reader.calls.length !== 1) throw new Error('Das reine Leserecht löst eine Mutation aus oder lädt die Historie nicht genau einmal.');
if (reader.buttonsCreated !== 0 || typeof reader.historyListeners.click === 'function') {
    throw new Error('Das reine Historienleserecht erhält eine widerrufende Bedienhandlung.');
}

console.log('Admin access permission split UI smoke passed.');
