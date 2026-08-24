import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../..', import.meta.url));
const viewSource = readFileSync(`${root}/js/retention-view.js`, 'utf8');

class FakeElement {
    constructor(tagName) { this.tagName = tagName.toUpperCase(); this.children = []; this.textContent = ''; this.className = ''; }
    append(...children) { this.children.push(...children); }
    replaceChildren(...children) { this.children = [...children]; }
}
const document = { createElement: (tag) => new FakeElement(tag) };
const window = { FilzmannDataProtection: {} };
vm.runInNewContext(viewSource, { window, document });

const container = new FakeElement('div');
window.FilzmannDataProtection.retentionView.render(container, {
    providers: { matrix: { displayName: 'Berechtigungsmatrix', status: 'complete', candidates: [{ reference: '<script>review</script>', reviewReason: 'Frist überschritten.', action: 'REVIEW' }] } },
});
const walk = (node) => [node, ...node.children.flatMap(walk)];
const nodes = walk(container);
const text = nodes.map((node) => node.textContent).join('\n');
if (!text.includes('<script>review</script>') || !text.includes('REVIEW')) throw new Error('REVIEW-Kandidat wird nicht als Text dargestellt.');
if (nodes.some((node) => node.tagName === 'SCRIPT')) throw new Error('Providerinhalt wurde als Markup ausgeführt.');

console.log('Retention review UI smoke passed.');
