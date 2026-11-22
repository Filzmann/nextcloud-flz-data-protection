import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../..', import.meta.url));
const sourcePath = `${root}/js/main.js`;
const source = readFileSync(sourcePath, 'utf8');
const domListeners = {};
const makeTab = (id, panelId, selected) => {
    const listeners = {};
    return {
        id,
        tabIndex: selected ? 0 : -1,
        attributes: { 'aria-controls': panelId, 'aria-selected': selected ? 'true' : 'false' },
        addEventListener(name, callback) { listeners[name] = callback; },
        getAttribute(name) { return this.attributes[name] ?? null; },
        setAttribute(name, value) { this.attributes[name] = String(value); },
        focus() { this.focused = true; },
        listeners,
    };
};
const reviewTab = makeTab('data-protection-review-tab', 'data-protection-review-panel', true);
const settingsTab = makeTab('data-protection-settings-tab', 'data-protection-settings-panel', false);
const panels = {
    'data-protection-review-panel': { hidden: false },
    'data-protection-settings-panel': { hidden: true },
};
const appRoot = { dataset: {} };
const document = {
    addEventListener(name, callback) { domListeners[name] = callback; },
    querySelectorAll(selector) { return selector === '.data-protection-tablist [role="tab"]' ? [reviewTab, settingsTab] : []; },
    getElementById(id) {
        if (id === 'data-protection-app') return appRoot;
        if (id === 'data-protection-retention-results') return null;
        return panels[id] ?? null;
    },
};

vm.runInNewContext(source, { document, window: {}, encodeURIComponent, Error }, { filename: sourcePath });
domListeners.DOMContentLoaded();
if (typeof settingsTab.listeners.click !== 'function' || typeof reviewTab.listeners.keydown !== 'function') {
    throw new Error('Die semantischen Tabs werden nicht interaktiv initialisiert.');
}

settingsTab.listeners.click();
if (settingsTab.getAttribute('aria-selected') !== 'true' || settingsTab.tabIndex !== 0 || panels['data-protection-settings-panel'].hidden) {
    throw new Error('Der Einstellungstab wird nicht vollständig aktiviert.');
}
if (reviewTab.getAttribute('aria-selected') !== 'false' || reviewTab.tabIndex !== -1 || !panels['data-protection-review-panel'].hidden) {
    throw new Error('Das zuvor aktive Panel bleibt nach Tabwechsel sichtbar oder fokussierbar.');
}

settingsTab.listeners.keydown({ key: 'ArrowLeft', preventDefault() {} });
if (!reviewTab.focused || reviewTab.getAttribute('aria-selected') !== 'true' || panels['data-protection-review-panel'].hidden) {
    throw new Error('Die Tabs sind nicht vollständig per Pfeiltaste bedienbar.');
}

console.log('Tab navigation UI smoke passed.');
