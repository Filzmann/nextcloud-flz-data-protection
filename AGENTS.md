# AGENTS.md – Filzmann Data Protection

## Zweck und Grenze

Dieses Repository enthält ausschließlich die eigenständig versionierte
Nextcloud-App `flz_data_protection`. Sie stellt den öffentlichen,
versionierten Datenschutz-Providervertrag, Registry, Aggregation, Coverage
und später Self-Service, Admin-Auskunft, Audit sowie Retention-Koordination
bereit. Fachapps bleiben Eigentümerinnen ihrer Daten.

Diese Datei und die beiden lokalen Skills bilden die vollständige Repository-Steuerung
für einen direkten Start in diesem App-Repository; der
Parent ist keine Laufzeitquelle für App-Regeln.

Offene app-lokale Produkt- und Retentionaufgaben stehen ausschließlich in
`ROADMAP.md`; implementierter Umfang steht in `README.md`.

Die App ist eine eigenständige Kategorie-B-Laufzeit-App ohne verpflichtende
Abhängigkeit zu LocalBase, OrgSuite oder einer Fachapp. Sie liest und ändert
niemals Tabellen, Entitäten, AppConfig-/UserConfig-Werte, Dateien oder
AppData-Strukturen anderer Apps direkt. Fehlende Provider bleiben sichtbar;
SQL-, Reflection-, Datei-, Volltext- oder Migrationsarchiv-Fallbacks sind
verboten.

## Lokale Arbeitsregeln

- Vor Änderungen `git status --short` prüfen und fremde Änderungen erhalten.
- Für App-Arbeit `.agents/skills/work-in-nextcloud-app/SKILL.md` vollständig
  lesen; Verhaltensänderungen folgen zusätzlich
  `.agents/skills/test-driven-change/SKILL.md`.
- Controller bleiben dünn. Öffentliche Verträge, Aggregation, Rechte,
  Darstellung, Persistenz und Jobs bleiben getrennt.
- Nextcloud-native Benutzer-, Session-, Gruppen-, Event-, AppConfig-,
  Capability-, Request- und Loggingmechanismen verwenden.
- Deny by default, Least privilege und server-side first gelten für jeden
  Self-Service-, Admin-, Export- und Retentionpfad. Navigation erteilt keine
  Rechte.
- Self-Service-Subjects entstehen ausschließlich aus der authentifizierten
  Session. Frei übermittelte Ziel-UIDs werden nicht akzeptiert.
- Providerfehler werden appweise isoliert und datensparsam diagnostiziert.
  Ein Teilbericht darf nie als vollständige Instanzauskunft erscheinen.
- Berichte werden ohne gesonderten Speicher- und Löschvertrag nicht zentral
  persistiert. V1-Retention bleibt ein reiner Dry Run. Der getrennte
  V2-Ausführungsvertrag ist standardmäßig deaktiviert und darf ausschließlich
  nach technischer Aktivierung durch native Nextcloud-Administration laufen.
  Kundenlokale Rechtsgrundlagen, Vereinbarungen, DPO-/BR-Bestätigungen und
  Evidenzreferenzen sind weder Eingabe noch Ausführungsgate; ihre Prüfung
  bleibt außerhalb des Produkts. Holds, Policy- und Versionsintegrität,
  Wirksamkeitszeit, Nebenläufigkeit, Providerkompatibilität sowie aktuelle
  Backup-/Restore-Prüfzeitpunkte bleiben technische Fail-closed-Gates.
- Native Nextcloud-Admins erhalten kein automatisches fachliches REVIEW-Recht.
  Der technische Adminbereich bleibt Nextcloud-nativ administrierbar; ein
  fachlicher Vollzugriff wird je Admin app-lokal, serverseitig und für
  höchstens 24 Stunden aktiviert. Beginn, geplantes Ende und Widerruf bleiben
  auditierbar. Konfigurierte Datenschutz-Prüfgruppen behalten ihre expliziten
  Fachrechte unabhängig davon.
- Die App hält ihren eigenen `PersonalDataProvider`,
  `ProcessingMetadataProvider` und `PermissionProvider` synchron zu neuen
  Verarbeitungen, Personenbezügen und Berechtigungen. Der app-eigene
  Processing-Katalog liegt ausschließlich unter
  `resources/privacy-processing.json`, enthält keine personenbezogenen
  Laufzeitdaten und weist fachliche Lücken als
  `PRIVACY-DECISION-REQUIRED` aus. Andere Apps werden ausschließlich über die
  öffentlichen Providerereignisse ausgewertet.
- Testdaten und Beispiele sind synthetisch, neutral und datenschutzarm.

## Stop-Gates

Vor Umsetzung werden Risiko, Dateien, Tests und Rückbau benannt und eine
ausdrückliche Freigabe eingeholt bei Datenbankschema/Migrationen,
Berechtigungen oder Gruppenlogik, öffentlichen Vertragsbrüchen,
repositoryübergreifenden Änderungen, Dateiablage oder Exportpersistenz,
Retention-Ausführung, Löschung/Anonymisierung sowie DDEV-/Nextcloud-/`occ`-
Zustandsänderungen. Bei konkurrierenden Wahrheiten, unklarer Migration oder
fehlendem sicheren Rückbau wird nicht implementiert.

## Tests und Definition of Done

- PHP: `php tests/run.php`
- JavaScript: `node tests/run-js.mjs`
- Zusätzlich `git diff --check` und die Parent-Strukturprüfung.
- Öffentliche Verträge benötigen Provider- und Consumer-Contract-Tests;
  Sicherheitsgrenzen mindestens einen sinnvollen Allow- und Deny-Fall.
- Neue oder wesentlich geänderte ausführbare PHP- und JavaScript-Logik zielt
  jeweils auf mindestens 85 Prozent Line-Coverage; Sicherheitsinvarianten
  werden vollständig abgedeckt.
- Kein Commit, Push, Release, Deployment, DDEV- oder `occ`-Lauf ohne
  ausdrückliche Freigabe.

## Dokumentenverantwortung

- `README.md` beschreibt ausschließlich den aktuellen nutzbaren Stand,
  Installation, Betrieb, Tests und den Dokumentationsindex.
- `ROADMAP.md` enthält ausschließlich offene, zurückgestellte oder
  freigabepflichtige Arbeit und Entscheidungen.
- `CHANGELOG.md` dokumentiert erledigte Änderungen releasebezogen; erledigte
  Checklisten verbleiben nicht in der Roadmap.
- `docs/architecture.md` ist die ausführliche Quelle für geltende fachliche
  und technische Architekturverträge.
- `docs/manual-acceptance.md` enthält wiederholbare manuelle Prüfungen und
  keine Produktplanung.
- `AGENTS.md` enthält ausschließlich verbindliche Arbeits-, Sicherheits-,
  Architektur- und Prüfregeln. Zusätzliche Dokumente werden in `README.md`
  mit eindeutiger Zuständigkeit eingeordnet.

## Parent-Governance-Vertrag: 2

- Die für dieses Subrepository anwendbaren Regeln des Parent-Workspaces sind
  verbindlich. Dazu gehören insbesondere app-übergreifende ADRs und
  öffentliche Verträge, Repositorygrenzen sowie Workspace-, Delivery- und
  Release-Gates.
- Diese lokale `AGENTS.md` und die lokalen Skills bleiben die vollständige,
  ohne Parent-Checkout arbeitsfähige Repository-Steuerung. Die anwendbaren
  Parent-Regeln werden dafür hier oder in den lokalen Skills mitgeführt.
- Repository-lokale Regeln dürfen Parent-Verträge konkretisieren und verschärfen,
  aber nicht abschwächen oder umgehen.
- Bei einem Widerspruch gilt bis zur Klärung die strengere Regel. Die Arbeit
  stoppt, bis die kanonische Quelle bestimmt, die Regelprojektionen
  synchronisiert und eine erforderliche Entscheidung dokumentiert ist.
- Ist der Parent-Workspace nicht verfügbar, bleibt die lokale Steuerung
  wirksam. Vor Cross-App-, Release- oder Delivery-Arbeit muss ein vermuteter
  neuerer Parent-Stand oder eine Regelungslücke zuerst gegen den Parent
  geprüft werden.

### Entwicklungsphase und Kompatibilitätsbedarf

Entscheidung vom 5. September 2026: Das Gesamtprojekt befindet sich vollständig
in der Entwicklung. Es gibt kein PROD, keinen produktiven Datenbestand und
keinen bereits betriebenen Bestand mit zu erhaltendem Upgradepfad. STAGING
ist eine wegwerfbare Entwicklungs- und Integrationsumgebung und darf im
konkret beauftragten Reinstall vollständig neu aufgebaut werden. Wenige
externe Testnutzer ändern diese Einordnung nicht.

Vor einer Datenmigration, Legacy-Unterstützung, Compatibility Layer,
Deprecated API, Dual-Read/Dual-Write, einem Altschema-Fallback, Übergangsformat
oder der Unterstützung historischer Entwicklungsstände wird geprüft:

1. Wurde der betroffene Zustand jemals produktiv eingesetzt?
2. Benötigen reale Daten oder Nutzer seine Erhaltung?
3. Gibt es einen anderen konkreten technischen Erhaltungsgrund, insbesondere
   einen geltenden Plattform- oder externen API-Vertrag?

Sind alle relevanten Antworten nein, ist die saubere Breaking-Change-/
Reinstall-Lösung der Standard. Frühere rein interne Entwicklungsstände
begründen weder Abwärtskompatibilität noch eine Deprecationfrist.
Entwicklungsschemata dürfen durch ein kanonisches Installationsschema ersetzt,
alte interne APIs und Konfigurationsformate samt ausschließlich dafür
benötigten Adaptern und Tests entfernt werden. Architekturqualität und der
saubere Zielzustand haben Vorrang. Nextclouds nötige Installationsmigrationen
bleiben erhalten; ein Verzeichnisname `Migration` beweist keine Altlast.

Breaking Changes werden im selben Änderungskontext vollständig durchgezogen:
betroffene Provider, Consumer, standardisierte APIs, Vertragsversionen,
Metadaten, Tests und Dokumentation müssen zusammenpassen. Unterstützte
Nextcloud-/openDesk-Plattformverträge, externe Standards, Autorisierung und
Datenschutz gelten unverändert. Fehlende oder inkompatible optionale Provider
bleiben kontrolliert sichtbar. Ein Reinstall erlaubt keine privaten
Fremdtabellenzugriffe oder parallel erfundenen Plattformmechanismen.

Vor destruktiver Arbeit werden die tatsächlich benötigten externen
Testidentitäten, Gruppen, Rollen und nicht reproduzierbaren Testdaten gezielt
gesichert oder über bestehende native Setup-Strukturen reproduzierbar gemacht.
Echte Personen- und Zugangsdaten bleiben außerhalb von Git. Diese begrenzte
Sicherung begründet keine allgemeine Legacy-Unterstützung. Ein Reinstall
bleibt ein normaler unterstützter Entwicklungsweg; der vorhandene
Compatibility-Workflow besitzt den Fresh-Install-Nachweis, dessen aktueller
Belegstatus in `docs/workspace.md` beschrieben ist.

Diese Phase endet ausschließlich durch einen ausdrücklich dokumentierten,
von Simon freigegebenen **Production-Readiness-/Production-Freeze-Entscheid**.
Ein Release Candidate, eine Versionsnummer, ein Staging-Deployment oder ein
externer Testzugang lösen den Wechsel nicht aus. Der Entscheid wird in dieser
kanonischen Lifecycle-Quelle mit Datum, Geltungsbereich und betroffenem
Versions-/Datenstand festgehalten und in die lokale Steuerung projiziert.
Dann werden Upgradepfade, Datenbankmigrationen, Persistenz, Backup/Restore,
Rollback, Release-/API-Kompatibilitätszusagen, Deployment-/Freigabeprozess und
PROD→STAGING/COPY-Strategie neu bewertet. Eine vollständige PROD-Governance
wird jetzt nicht vorweggenommen.

Diese Regel entscheidet den Kompatibilitätsbedarf, erweitert aber keinen
Repository-Schreibauftrag und ersetzt keine Freigabe für eine konkrete
destruktive Aktion. Lokale Regelprojektionen folgen dem bestehenden
`docs/parent-governance-contract.md`; ein unsynchronisierter Einzel-Checkout
darf keinen abweichenden Phasenstand stillschweigend annehmen.

### Prüfaufwand

- Vor einem neuen Test, Scan, Linter, Architektur- oder Systemcheck wird
  geprüft, welcher bestehende Check dieselbe Eigenschaft bereits nachweist.
  Diesen erweitern oder sein nachweislich passendes Ergebnis wiederverwenden;
  ein zusätzlicher Check braucht eine benannte zusätzliche Fehlerklasse oder
  Vertrauensgrenze. Gleicher Input, gleiche Prüfung, gleiche Fehlerklasse und
  gleiche Phase begründen keinen zweiten Lauf. Gestaffelte Unit-, Contract-
  und Runtime-Nachweise bleiben erhalten. Die dokumentierten lokalen
  Prüfeinstiege bestimmen Umfang und Ergebnisgültigkeit.
