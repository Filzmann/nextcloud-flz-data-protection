import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';

const rootPath = fileURLToPath(new URL('../..', import.meta.url));
const source = readFileSync(`${rootPath}/js/main.js`, 'utf8');
const root = { dataset: {} };
const results = {};
let readyCallback = null;
let fetchOptions = null;
let renderedReport = null;

const document = {
    addEventListener: (eventName, callback) => {
        if (eventName === 'DOMContentLoaded') readyCallback = callback;
    },
    getElementById: (id) => id === 'data-protection-app' ? root : (id === 'data-protection-results' ? results : null),
};
const report = { coverageComplete: false, providers: {} };
const fetch = async (_url, options) => {
    fetchOptions = options;
    return { ok: true, json: async () => report };
};
const window = {
    FlzDataProtection: {
        reportView: {
            render: (_container, value) => { renderedReport = value; },
        },
    },
};
const OC = {
    requestToken: 'synthetic-request-token',
    generateUrl: (path) => path,
};

vm.runInNewContext(source, { document, fetch, window, OC });
if (readyCallback === null) throw new Error('Self-Service registriert keinen DOM-Start.');
readyCallback();
await new Promise((resolve) => setTimeout(resolve, 0));

if (fetchOptions?.headers?.requesttoken !== OC.requestToken) {
    throw new Error('Der geschützte Self-Service-Abruf sendet Nextclouds CSRF-Requesttoken nicht.');
}
if (fetchOptions?.credentials !== 'same-origin') {
    throw new Error('Der Self-Service-Abruf ist nicht an die Nextcloud-Sitzung gebunden.');
}
if (renderedReport !== report || root.dataset.ready !== 'true') {
    throw new Error('Die erfolgreiche Self-Service-Antwort wird nicht vollständig verarbeitet.');
}

console.log('Self-service request security smoke passed.');
