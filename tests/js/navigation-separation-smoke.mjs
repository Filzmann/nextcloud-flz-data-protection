import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../..', import.meta.url));
const appTemplate = readFileSync(`${root}/templates/index.php`, 'utf8');
const personalTemplate = readFileSync(`${root}/templates/personal.php`, 'utf8');
const style = readFileSync(`${root}/css/style.css`, 'utf8');

for (const contract of [
    'role="tablist"',
    'role="tab"',
    'role="tabpanel"',
    'id="data-protection-settings-tab"',
    'id="data-protection-settings-panel"',
    'id="data-protection-review-tab"',
    'id="data-protection-review-panel"',
    'class="data-protection-area-grid"',
]) {
    if (!appTemplate.includes(contract)) throw new Error(`Semantische App-Bereichstrennung fehlt: ${contract}`);
}

if (!appTemplate.includes('aria-controls="data-protection-settings-panel"') || !appTemplate.includes('aria-labelledby="data-protection-settings-tab"')) throw new Error('Einstellungstab und Panel sind nicht semantisch verbunden.');
if (!appTemplate.includes('aria-controls="data-protection-review-panel"') || !appTemplate.includes('aria-labelledby="data-protection-review-tab"')) throw new Error('REVIEW-Tab und Panel sind nicht semantisch verbunden.');
if (!personalTemplate.includes('aria-labelledby="data-protection-personal-heading"')) {
    throw new Error('Persönlicher Self-Service besitzt keine eindeutige semantische Überschrift.');
}
if (!/\.data-protection-tab:focus-visible\s*\{[^}]*outline:/s.test(style)) {
    throw new Error('Sichtbarer Tastaturfokus der Bereichsnavigation fehlt.');
}
if (!/\.data-protection-area-grid\s*\{[^}]*display:\s*grid[^}]*grid-template-columns:\s*repeat\(auto-fit,\s*minmax\(min\(100%,\s*360px\),\s*1fr\)\)/s.test(style)) {
    throw new Error('Responsives Bereichsraster fehlt.');
}
if (!/\.data-protection-tablist\s*\{[^}]*overflow-x:\s*auto/s.test(style)) throw new Error('Tabs bleiben in schmalen Ansichten nicht horizontal erreichbar.');
if (!/\.data-protection-personal\s*\{[^}]*min-width:\s*0/s.test(style)) {
    throw new Error('Persönlicher Einstellungsbereich kann in schmalen Nextcloud-Layouts überlaufen.');
}

console.log('Personal/app navigation separation smoke passed.');
