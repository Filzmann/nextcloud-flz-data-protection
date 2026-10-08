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
const window = { FlzDataProtection: {} };
vm.runInNewContext(source, { window, document });

const container = new FakeElement('div');
window.FlzDataProtection.reportView.render(container, {
    coverageComplete: false,
    discoveryStatus: 'complete',
    providers: {
        reference_app: {
            status: 'partial',
            restrictions: ['Weitere Datensätze können vorhanden sein.'],
            nextCursor: 'opaque-cursor',
            entries: Array.from({ length: 5 }, (_, index) => ({
                categoryId: 'planning_activity',
                categoryLabel: 'Bearbeitungsnachweis',
                reference: `run_updated:${index + 1}`,
                summary: 'Durchlauf bearbeitet',
                purpose: 'Nachvollziehbarkeit der Planung',
                source: 'Änderungsprotokoll der App',
                recipientCategories: ['Berechtigte Planungsverantwortliche'],
                retention: index === 4 ? 'Abweichende Prüffrist' : 'Prüfung erforderlich',
                thirdCountryTransfer: 'Keine',
                automatedDecision: 'Keine',
                thirdPartyContentNotice: null,
                attributes: {
                    Vorgang: 'Durchlauf bearbeitet',
                    Zeitpunkt: `${index + 1}. August 2026`,
                    Protokollart: '<script>nicht ausführen</script>',
                    Hinweis: index === 0 ? '<img src=x onerror=alert(1)>' : null,
                },
            })),
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
if (text.includes('run_updated:2')) {
    throw new Error('Eine technische Referenz wird unverändert Beschäftigten angezeigt.');
}
for (const expected of ['Gilt für alle folgenden Daten', '5 gespeicherte Einträge', 'Durchlauf bearbeitet: 5-mal protokolliert.', 'Bearbeitungsnachweis Nr. 2', 'Nachvollziehbarkeit der Planung']) {
    if (!text.includes(expected)) throw new Error(`Die kompakte verständliche Auskunft fehlt: ${expected}`);
}
if (text.split('Nachvollziehbarkeit der Planung').length - 1 !== 1) {
    throw new Error('Der für alle Einträge identische Zweck wird wiederholt dargestellt.');
}
if (text.split('<script>nicht ausführen</script>').length - 1 !== 1) {
    throw new Error('Ein gemeinsames Datenfeld wird statt einmal vor der Datenliste mehrfach dargestellt.');
}
if (!text.includes('Abweichende Prüffrist') || !text.includes('Prüfung erforderlich')) {
    throw new Error('Unterschiedliche Aufbewahrungsangaben wurden unzulässig zusammengefasst.');
}
const tables = nodes.filter((node) => node.tagName === 'TABLE');
if (tables.length !== 1) {
    throw new Error('Gleichartige Datensätze werden nicht in einer kompakten Tabelle zusammengefasst.');
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
