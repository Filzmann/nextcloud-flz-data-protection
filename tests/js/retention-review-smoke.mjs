import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../..', import.meta.url));
const viewSource = readFileSync(`${root}/js/retention-view.js`, 'utf8');

class FakeElement {
    constructor(tagName) { this.tagName = tagName.toUpperCase(); this.children = []; this.textContent = ''; this.className = ''; this.listeners = {}; this.dataset = {}; this.parentNode = null; }
    append(...children) { children.forEach((child) => { child.parentNode = this; this.children.push(child); }); }
    replaceChildren(...children) { this.children = []; this.append(...children); }
    addEventListener(type, listener) { this.listeners[type] = listener; }
    remove() { if (this.parentNode) this.parentNode.children = this.parentNode.children.filter((child) => child !== this); }
    querySelector(selector) { return this.querySelectorAll(selector)[0] || null; }
    querySelectorAll(selector) {
        const descendants = this.children.flatMap((child) => [child, ...child.querySelectorAll(selector)]);
        if (selector.startsWith('.')) return descendants.filter((node) => node.className.split(' ').includes(selector.slice(1)));
        if (selector === 'button[data-continuation]') return descendants.filter((node) => node.tagName === 'BUTTON' && node.dataset.continuation);
        return [];
    }
}
const document = { createElement: (tag) => new FakeElement(tag) };
const window = { FlzDataProtection: {} };
vm.runInNewContext(viewSource, { window, document });

const container = new FakeElement('div');
window.FlzDataProtection.retentionView.render(container, {
    providers: { matrix: { displayName: 'Berechtigungsmatrix', status: 'partial', candidates: [{ reference: '<script>review</script>', reviewReason: 'Frist überschritten.', action: 'REVIEW' }], continuations: { export_review: 'opaque-token' } } },
});
const walk = (node) => [node, ...node.children.flatMap(walk)];
const nodes = walk(container);
const text = nodes.map((node) => node.textContent).join('\n');
if (!text.includes('<script>review</script>') || !text.includes('REVIEW')) throw new Error('REVIEW-Kandidat wird nicht als Text dargestellt.');
if (nodes.some((node) => node.tagName === 'SCRIPT')) throw new Error('Providerinhalt wurde als Markup ausgeführt.');
const loadMore = nodes.find((node) => node.tagName === 'BUTTON');
if (!loadMore || loadMore.dataset.continuation !== 'opaque-token') throw new Error('Explizite Fortsetzung fehlt.');
let requestedContinuation = null;
window.FlzDataProtection.retentionView.render(container, {
    providers: { matrix: { displayName: 'Berechtigungsmatrix', status: 'partial', candidates: [], continuations: { export_review: 'opaque-token' } } },
}, (continuation) => { requestedContinuation = continuation; });
container.querySelector('button[data-continuation]').listeners.click();
if (requestedContinuation !== 'opaque-token') throw new Error('Fortsetzungsbutton löst keinen expliziten Request aus.');
window.FlzDataProtection.retentionView.append(container, {
    providers: { matrix: { displayName: 'Berechtigungsmatrix', status: 'complete', candidates: [{ reference: 'matrix:2', reviewReason: 'Zweite Seite.', action: 'REVIEW' }], continuations: {} } },
});
const appendedText = walk(container).map((node) => node.textContent).join('\n');
if (!appendedText.includes('matrix:2') || !appendedText.includes('Vorschau vollständig')) throw new Error('Folgeseite oder finaler Vollständigkeitsstatus fehlt.');
if (appendedText.includes('Keine fälligen REVIEW-Kandidaten.') || container.querySelector('button[data-continuation]')) throw new Error('Veralteter Leerhinweis oder Fortsetzungsbutton blieb nach der letzten Seite stehen.');

console.log('Retention review UI smoke passed.');
