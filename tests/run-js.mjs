import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('..', import.meta.url));
const read = (path) => readFileSync(`${root}/${path}`, 'utf8');

const info = read('appinfo/info.xml');
const routes = read('appinfo/routes.php');
const pageController = read('lib/Controller/PageController.php');
const template = read('templates/index.php');
const style = read('css/style.css');
const script = read('js/main.js');

if (!info.includes('<id>filzmann_data_protection</id>')) throw new Error('Eindeutige App-ID fehlt.');
if (!info.includes('<namespace>FilzmannDataProtection</namespace>')) throw new Error('App-Namespace fehlt.');
if (info.includes('<app>')) throw new Error('Die Standalone-App besitzt eine fremde Laufzeitabhängigkeit.');
if (!info.includes('<route>filzmann_data_protection.page.index</route>')) throw new Error('Standalone-Navigation fehlt.');
if (!routes.includes("'name' => 'page#index'")) throw new Error('App-Route fehlt.');
if (!pageController.includes('#[NoAdminRequired]')) throw new Error('Angemeldete Nicht-Admins erhalten keinen Self-Service-Einstieg.');
if (pageController.includes('PublicPage')) throw new Error('Der Self-Service-Einstieg wurde anonym freigegeben.');
if (!template.includes('id="data-protection-app"')) throw new Error('Semantischer App-Root fehlt.');
if (!template.includes('Noch sind keine Datenschutzprovider registriert.')) throw new Error('Fehlende Provider werden nicht transparent ausgewiesen.');
if (!/\#data-protection-app\s*\{[^}]*height:\s*100%[^}]*min-height:\s*0[^}]*overflow-y:\s*auto/s.test(style)) throw new Error('Scrollvertrag fehlt.');
if (!script.includes("dataset.ready = 'true'")) throw new Error('App-Initialisierung fehlt.');

console.log('Filzmann Data Protection JavaScript/app contract passed.');
