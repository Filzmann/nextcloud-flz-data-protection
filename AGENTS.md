# AGENTS.md – Filzmann Data Protection

## Zweck und Grenze

Dieses Repository enthält ausschließlich die eigenständig versionierte
Nextcloud-App `filzmann_data_protection`. Sie stellt den öffentlichen,
versionierten Datenschutz-Providervertrag, Registry, Aggregation, Coverage
und später Self-Service, Admin-Auskunft, Audit sowie Retention-Koordination
bereit. Fachapps bleiben Eigentümerinnen ihrer Daten.

Diese Datei und die beiden lokalen Skills bilden die vollständige Repository-Steuerung
für einen direkten Start in diesem App-Repository; der
Parent ist keine Laufzeitquelle für App-Regeln.

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
  persistiert. Retention beginnt ausschließlich mit Dry Run; unbekannte oder
  widersprüchliche Zustände lösen keine destruktive Maßnahme aus.
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
