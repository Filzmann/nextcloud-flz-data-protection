import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../..', import.meta.url));
const source = readFileSync(`${root}/js/report-view.js`, 'utf8');

class FakeElement {
    constructor(tagName) {
        this.tagName = tagName.toUpperCase();
        this.children = [];
        this.attributes = {};
        this.className = '';
        this.textContent = '';
    }

    append(...children) {
        this.children.push(...children);
    }

    replaceChildren(...children) {
        this.children = [...children];
    }

    setAttribute(name, value) {
        this.attributes[name] = String(value);
    }
}

const document = {
    createElement: (tagName) => new FakeElement(tagName),
};
const window = { FilzmannDataProtection: {} };
vm.runInNewContext(source, { window, document });

const container = new FakeElement('div');
window.FilzmannDataProtection.reportView.render(container, {
    coverageComplete: false,
    discoveryStatus: 'complete',
    providers: {
        reference_app: {
            status: 'partial',
            restrictions: ['Weitere Datensätze können vorhanden sein.'],
            nextCursor: 'opaque-cursor',
            entries: [{
                categoryLabel: 'Buchung',
                reference: 'booking:17',
                summary: '<img src=x onerror=alert(1)>',
                purpose: 'Raumkoordination',
                source: 'Sitzungskonto',
                recipientCategories: ['Instanzbenutzer*innen'],
                retention: 'Prüfung erforderlich',
                thirdCountryTransfer: 'Keine',
                automatedDecision: 'Keine',
                thirdPartyContentNotice: null,
                attributes: { Raum: '<script>nicht ausführen</script>' },
            }],
        },
        missing_app: {
            status: 'missing',
            restrictions: ['Expected provider unavailable.'],
            nextCursor: null,
            entries: [],
        },
    },
});

const walk = (node) => [node, ...node.children.flatMap(walk)];
const nodes = walk(container);
const text = nodes.map((node) => node.textContent).join('\n');

if (!text.includes('nicht vollständig') || !text.includes('Teilweise') || !text.includes('Fehlt')) {
    throw new Error('Vollständigkeits- oder Providerstatus wird nicht verständlich dargestellt.');
}
if (!text.includes('<img src=x onerror=alert(1)>') || !text.includes('<script>nicht ausführen</script>')) {
    throw new Error('Providerdaten werden nicht als Text dargestellt.');
}
if (nodes.some((node) => node.tagName === 'IMG' || node.tagName === 'SCRIPT')) {
    throw new Error('Providerdaten wurden als ausführbares Markup interpretiert.');
}
const headers = nodes.filter((node) => node.tagName === 'TH');
if (headers.filter((header) => header.attributes.scope === 'col').length < 2) {
    throw new Error('Datentabelle besitzt keine semantischen Spaltenköpfe.');
}
if (!nodes.some((node) => node.attributes.role === 'status')) {
    throw new Error('Vollständigkeitsstatus besitzt keine zugängliche Statussemantik.');
}

console.log('Self-service report UI smoke passed.');
