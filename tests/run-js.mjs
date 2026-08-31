import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('..', import.meta.url));
const read = (path) => readFileSync(`${root}/${path}`, 'utf8');

const info = read('appinfo/info.xml');
const routes = read('appinfo/routes.php');
const pageController = read('lib/Controller/PageController.php');
const selfServiceController = read('lib/Controller/SelfServiceController.php');
const template = read('templates/index.php');
const style = read('css/style.css');
const script = read('js/main.js');
const reportView = read('js/report-view.js');
const adminTemplate = read('templates/admin.php');
const adminScript = read('js/admin.js');

if (!info.includes('<id>filzmann_data_protection</id>')) throw new Error('Eindeutige App-ID fehlt.');
if (!info.includes('<namespace>FilzmannDataProtection</namespace>')) throw new Error('App-Namespace fehlt.');
if (info.includes('<app>')) throw new Error('Die Standalone-App besitzt eine fremde Laufzeitabhängigkeit.');
if (!info.includes('<route>filzmann_data_protection.page.index</route>')) throw new Error('Standalone-Navigation fehlt.');
if (!routes.includes("'name' => 'page#index'")) throw new Error('App-Route fehlt.');
if (!routes.includes("'name' => 'self_service#report'")) throw new Error('Self-Service-API-Route fehlt.');
if (!routes.includes("'url' => '/api/v1/self-service-report'")) throw new Error('Self-Service-API besitzt keinen stabilen Pfad.');
if (!routes.includes("'url' => '/api/v1/retention-review'")) throw new Error('Geschützte REVIEW-API fehlt.');
if (!pageController.includes('#[NoAdminRequired]')) throw new Error('Angemeldete Nicht-Admins erhalten keinen Self-Service-Einstieg.');
if (pageController.includes('PublicPage')) throw new Error('Der Self-Service-Einstieg wurde anonym freigegeben.');
if (!selfServiceController.includes('#[NoAdminRequired]')) throw new Error('Angemeldete Nicht-Admins erhalten keine Self-Service-Auskunft.');
if (selfServiceController.includes('PublicPage')) throw new Error('Die Self-Service-Auskunft wurde anonym freigegeben.');
if (!/function report\(\): JSONResponse/.test(selfServiceController)) throw new Error('Die Self-Service-API akzeptiert eine frei übermittelte Zielperson.');
if (!template.includes('id="data-protection-app"')) throw new Error('Semantischer App-Root fehlt.');
if (!template.includes('id="data-protection-retention"')) throw new Error('Operativer REVIEW-Bereich fehlt.');
if (!info.includes('<admin>OCA\\FilzmannDataProtection\\Settings\\Admin</admin>')) throw new Error('Datenschutz-Administration fehlt.');
if (!adminTemplate.includes('kein automatisches fachliches Leserecht')) throw new Error('Deny-by-default-Hinweis für native Admins fehlt.');
if (!adminTemplate.includes('id="data-protection-full-access-form"')) throw new Error('App-lokale Adminfreigabe fehlt.');
if (!adminTemplate.includes('value="1440"')) throw new Error('Die maximal kaufbare Dauer von 24 Stunden fehlt.');
if (adminTemplate.includes('name="allow_nextcloud_admin_review"')) throw new Error('Der alte unbefristete Adminzugriff ist noch konfigurierbar.');
if (!routes.includes("'url' => '/api/v1/admin/full-access'")) throw new Error('Adminfreigabe-API fehlt.');
if (!adminScript.includes('data-protection-full-access-history')) throw new Error('Freigabehistorie wird nicht dargestellt.');
if (!adminScript.includes('requesttoken: OC.requestToken')) throw new Error('Einstellungsspeicherung sendet kein CSRF-Token.');
if (!reportView.includes('Noch sind keine Datenschutzprovider registriert.')) throw new Error('Fehlende Provider werden nicht transparent ausgewiesen.');
if (!script.includes('/api/v1/self-service-report')) throw new Error('Self-Service-Bericht wird nicht geladen.');
if (!script.includes("setAttribute('role', 'alert')")) throw new Error('Ladefehler besitzt keine zugängliche Fehlermeldung.');
if (!/\#data-protection-app\s*\{[^}]*height:\s*100%[^}]*min-height:\s*0[^}]*overflow-y:\s*auto/s.test(style)) throw new Error('Scrollvertrag fehlt.');
if (!script.includes("dataset.ready = 'true'")) throw new Error('App-Initialisierung fehlt.');

console.log('Filzmann Data Protection JavaScript/app contract passed.');

await import('./js/self-service-request-smoke.mjs');
await import('./js/self-service-report-smoke.mjs');
await import('./js/retention-review-smoke.mjs');
await import('./js/compact-report-layout-smoke.mjs');
