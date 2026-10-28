import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../..', import.meta.url));
const appTemplate = readFileSync(`${root}/templates/index.php`, 'utf8');
const personalTemplate = readFileSync(`${root}/templates/personal.php`, 'utf8');
const style = readFileSync(`${root}/css/style.css`, 'utf8');

for (const contract of [
    'class="data-protection-area-navigation"',
    'aria-label="Bereiche des Datenschutz-Centers"',
    'id="data-protection-governance"',
    'id="data-protection-technical-operations"',
    'id="data-protection-review-area"',
    'class="data-protection-area-grid"',
]) {
    if (!appTemplate.includes(contract)) throw new Error(`Semantische App-Bereichstrennung fehlt: ${contract}`);
}

for (const target of ['#data-protection-governance', '#data-protection-technical-operations', '#data-protection-review-area']) {
    if (!appTemplate.includes(`href="${target}"`)) throw new Error(`Tastaturbedienbarer Bereichslink fehlt: ${target}`);
}
if (!personalTemplate.includes('aria-labelledby="data-protection-personal-heading"')) {
    throw new Error('Persönlicher Self-Service besitzt keine eindeutige semantische Überschrift.');
}
if (!/\.data-protection-area-navigation a:focus-visible\s*\{[^}]*outline:/s.test(style)) {
    throw new Error('Sichtbarer Tastaturfokus der Bereichsnavigation fehlt.');
}
if (!/\.data-protection-area-grid\s*\{[^}]*display:\s*grid[^}]*grid-template-columns:\s*repeat\(auto-fit,\s*minmax\(min\(100%,\s*360px\),\s*1fr\)\)/s.test(style)) {
    throw new Error('Responsives Bereichsraster fehlt.');
}
if (!/@media\s*\(max-width:\s*600px\)[\s\S]*\.data-protection-area-navigation\s*\{[^}]*align-items:\s*stretch/s.test(style)) {
    throw new Error('Schmale Ansichten stapeln die Bereichsnavigation nicht robust.');
}
if (!/\.data-protection-personal\s*\{[^}]*min-width:\s*0/s.test(style)) {
    throw new Error('Persönlicher Einstellungsbereich kann in schmalen Nextcloud-Layouts überlaufen.');
}

console.log('Personal/app navigation separation smoke passed.');
