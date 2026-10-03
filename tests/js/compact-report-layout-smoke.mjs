import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../..', import.meta.url));
const css = readFileSync(`${root}/css/style.css`, 'utf8');

const contracts = [
    [/\.data-protection-report\s*\{[^}]*padding:\s*16px/s, 'Der äußere Berichtsbereich ist nicht kompakt.'],
    [/\.data-protection-provider\s*\{[^}]*margin-top:\s*12px[^}]*padding:\s*14px/s, 'App-Karten benötigen weiterhin zu viel Platz.'],
    [/\.data-protection-entry\s*\{[^}]*margin-top:\s*10px[^}]*padding:\s*12px/s, 'Datentyp-Karten benötigen weiterhin zu viel Platz.'],
    [/\.data-protection-common\s*\{[^}]*margin:\s*8px\s+0[^}]*padding:\s*8px\s+10px/s, 'Gemeinsame graue Angaben sind nicht kompakt.'],
    [/\.data-protection-definition\s*\{[^}]*gap:\s*2px\s+12px[^}]*padding:\s*1px\s+0/s, 'Metadatenzeilen sind nicht kompakt.'],
    [/\.data-protection-definition dt,\s*\.data-protection-definition dd\s*\{[^}]*margin:\s*0[^}]*padding:\s*0[^}]*line-height:\s*1\.35/s, 'Nextcloud-Standardabstände zwischen Metadatenzeilen wurden nicht zurückgesetzt.'],
    [/\.data-protection-table th,\s*\.data-protection-table td\s*\{[^}]*padding:\s*5px\s+6px/s, 'Tabellenzeilen sind nicht kompakt.'],
];

for (const [pattern, message] of contracts) {
    if (!pattern.test(css)) throw new Error(message);
}

if (!/\.data-protection-table-wrapper\s*\{[^}]*overflow-x:\s*auto/s.test(css)) {
    throw new Error('Die Verdichtung darf den horizontalen Tabellenscroll nicht entfernen.');
}

const reportView = readFileSync(`${root}/js/report-view.js`, 'utf8');
for (const [pattern, message] of [
    [/\.app-horizontal-scroll-proxy\s*\{[^}]*position:\s*sticky[^}]*bottom:\s*0[^}]*overflow-x:\s*auto/s, 'Der permanente Proxy liegt nicht am unteren sichtbaren App-Rand.'],
    [/\.app-horizontal-scroll-proxy\[hidden\]/, 'Der Proxy wird ohne Überlauf nicht ausgeblendet.'],
    [/className = 'app-horizontal-scroll-proxy'/, 'Der Proxy besitzt keinen eindeutigen Namen.'],
    [/ResizeObserver/, 'Größenänderungen werden nicht beobachtet.'],
    [/proxy\.addEventListener\('scroll'/, 'Proxy-Scroll wird nicht zum Ziel synchronisiert.'],
    [/target\.addEventListener\('scroll'/, 'Ziel-Scroll wird nicht zum Proxy synchronisiert.'],
    [/container\.querySelector\?\.\('\.data-protection-table-wrapper'\)/, 'Nur der dynamische Berichts-Tabellenscroller darf Ziel sein.'],
]) {
    if (!pattern.test(`${css}\n${reportView}`)) throw new Error(message);
}

console.log('Compact data protection report layout smoke passed.');
