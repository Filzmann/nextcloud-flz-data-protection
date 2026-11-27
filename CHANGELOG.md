# Changelog

## Unreleased

- L10N-Migration (ZM-06): sichtbare Texte über Nextcloud-l10n (deutsche Quelltexte, Kataloge `l10n/en.*` + `l10n/en_GB.*`), inkl. `tests/Ui/L10nCatalogContractTest.php` und `docs/l10n-inventory.md`.

- Die Gruppenauswahl der Datenschutzrechte konsumiert ausschließlich den
  aktivierten öffentlichen V1-Vertrag von `flz_organization`. Fehlende,
  inkompatible, ungültige und fehlerhafte Provider liefern keine
  Organisationsgruppen und werden nicht durch LocalBase ersetzt. Die
  app-eigene Standardgruppe `Datenschutzbeauftragte` bleibt unabhängig nutzbar.
- Providerfehler werden ausschließlich mit technischer Fehlerklasse
  protokolliert; der neue Consumer ist durch Allow-/Deny-Verhaltenstests und
  eine echte, rein lesende DDEV-Providerprobe abgesichert.
- Die native Aktivierungsprüfung des Organisationsproviders unterstützt auch
  die deklarierte Nextcloud-29–31-API. Der Regressionstest prüft die ältere
  und die aktuelle API-Form einschließlich deaktivierter und fehlerhafter
  Provider; er ersetzt keine vollständige Laufzeitmatrix dieser Versionen.
- JavaScript-VM-Smokes verknüpfen ihre Ausführung mit dem absoluten Pfad der
  jeweils gelesenen Quelldatei, damit die Coverage-Messung sie korrekt zuordnet.
- Isolierte V2-Runtime-Abnahme auf Nextcloud 34.0.2 und PostgreSQL 16
  abgeschlossen: Fresh Install und Reinstall, Provider-Discovery,
  Aktivierungsgrenzen, echte DELETE-Transaktionen, Holds,
  Background-Job-Wiederanlauf und Idempotenz, Nebenläufigkeit, Rollback vor
  Commit sowie Restore-Quarantäne sind grün. Ein historischer V2-Upgradepfad
  bleibt mangels autoritativem Vorzustand im Entwicklungsprojekt ausdrücklich
  nicht behauptet; der kanonische Reinstall ist nachgewiesen.
- Fachliche Datenschutzrechte von Organigrammgruppen getrennt: acht
  serverseitig geprüfte Einzelrechte besitzen jeweils
  `Datenschutzbeauftragte` als Default und können im geschützten
  Einstellungstab über Organigramm-Auswahlmenüs separat zugeordnet werden.
  Verwaltung und Lesen der temporären Adminfreigabehistorie sind unabhängig;
  native Administration behält ausschließlich die technische
  Retention-Aktivierung und erhält keinen fachlichen Vollzugriff.
- Den sessiongebundenen Art.-15-Self-Service aus dem App-Menü in den nativen
  persönlichen Nextcloud-Abschnitt `Datenschutz` verschoben. Fachliche
  Datenschutzsteuerung, technischer Betrieb und REVIEW bleiben im App-Menü
  als semantisch getrennte, tastatur- und responsive nutzbare Bereiche; die
  bestehenden serverseitigen Allow-/Deny-Grenzen bleiben unverändert.
- Technische App-Identität auf `flz_data_protection` und
  `OCA\\FlzDataProtection` umgestellt. App-eigene Tabellen und Indizes
  verwenden für den vereinbarten Fresh-Install-Neustart das Präfix `flz_dp`;
  die sichtbare Filzmann-Produktbezeichnung bleibt erhalten.
- Retention-Runtime repariert: der ausführbare Adminhistorien-Provider ist
  nun explizit an sein Repository gebunden, fehlgeschlagene Discovery-,
  Registrierungs- oder Ausführungsläufe werden datensparsam diagnostiziert
  und vom Background-Job nicht mehr als Erfolg quittiert. Die App-Version ist
  auf `0.1.5` erhöht, damit Installationen mit `0.1.4` die neuen additiven
  Retention-Migrationen als Upgrade erkennen.
- Öffentlichen V2-Ausführungsvertrag mit lazy Providerregistrierung,
  Versionsprüfung, festen DELETE-Policies, Hold-Prüfung und stündlicher
  Koordination ergänzt. Die Daten bleiben in der jeweils besitzenden App;
  Provider führen jeden Kandidaten atomar, nebenläufigkeitssicher und
  idempotent aus oder brechen fail-closed ab.
- Technische Retention-Aktivierung als standardmäßig deaktivierte,
  append-only Revision für native Nextcloud-Administration ergänzt. Sie
  verlangt aktuelle Backup- und Restore-Prüfzeitpunkte, maximal 365 Tage
  Backupaufbewahrung plus fünf Tage Puffer und aktiviert ausschließlich die
  empfohlenen P1Y-Buchungs- und P6M-Adminhistorien-Policies. Kundenlokale
  Rechtsquellen, Vereinbarungen, DPO-/BR-Bestätigungen und Evidenzen werden
  nicht abgefragt und blockieren die Ausführung nicht. Holds, ungültige oder
  abgelaufene technische Zustände und inkompatible Provider blockieren weiter.
- Neutralen öffentlichen V1-Risikoscope-Vertrag ergänzt. Kundenlokale
  Policy-, Evidenz- und Gültigkeitswerte bleiben private,
  DPO-geschützte, app-eigene DB-Revisionen; ein eindeutiger Schlüssel aus
  Scope und Revision weist konkurrierende Schreibvorgänge atomar ab. Consumer erhalten nur einen
  versionsgeprüften neutralen Freigabestatus. Fehlende, deaktivierte,
  zukünftige, abgelaufene, beschädigte und inkompatible Zustände sperren ohne
  Nebenwirkung. Das Verbot von Leistungs- und Verhaltenskontrolle bleibt
  unveränderlich.
- Additive, revisionsgeführte optionale Instanzdokumentation für die
  geschlossenen deutschen Rechtsprofile ergänzt. Nur Konten mit dem
  zugehörigen app-lokalen Einzelrecht sehen und bearbeiten sie; unvollständige Eingaben
  erzeugen keine Revision, und jede
  spätere Änderung wird ausschließlich als neue, appseitig unveränderliche Revision
  angefügt. Diese Dokumentation erzeugt keinen Job- oder Löschpfad und ist
  ausdrücklich kein Gate der getrennten technischen Aktivierung.
  Das Rechteinventar führt die DPO-Capability explizit; eigene Revisionen
  werden subjectgebunden paginiert, und ihre separate Aufbewahrung bleibt als
  Datenschutzentscheidung offen.
- Die kanonische Nextcloud-Gruppe `Datenschutzbeauftragte` wird bei
  Installation beziehungsweise Upgrade idempotent über die native
  Gruppenverwaltung angelegt, ohne bestehende Gruppen oder Mitglieder zu ändern.
- Reale Nextcloud-34-Lifecycle-Matrix für Filzmann Raumplaner und Filzmann Urlaubsplanung ergänzt: alter LocalBase-Pilot, gemeinsames Update auf den Standalone-V1-Vertrag, deaktivierte und entfernte Privacy-App, Neuinstallation sowie vorwärtsversionierter Rollback sind grün; Provider-Discovery bleibt in jedem Zustand explizit.
- Öffentlichen V1-Retention-Previewvertrag um feste Bewertungszeitpunkte,
  opake seitenweise Fortsetzungen, isolierte Providerfehler und robuste
  Nachladezustände ergänzt; Filzmann Raumplaner und Filzmann Urlaubsplanung vom LocalBase-Pilot
  als eigenständige Consumer migriert.
- App-lokale Adminfreigabe aus der technischen Administration in den geschützten App-Einstieg
  verschoben und um rollenabhängige Eintrittsmeldung, Direktlink sowie
  Allow-, Deny- und Manipulationsnachweise ergänzt.
- Sechsmonats-Standard der eigenen Adminfreigabehistorie über das zugehörige
  app-lokale Einzelrecht versioniert konfigurierbar gemacht und als
  datenminimierte, rückwirkend vom tatsächlichen Ende berechnete
  `REVIEW`-Vorschau registriert; V1 besitzt weiterhin keinen Ausführungspfad.
- Dokumentations- und Steuerungsstruktur vereinheitlicht.
- Additiven öffentlichen V1-Processing-Metadata-Providervertrag, lazy Registry,
  Fehlerisolation, Contract-Test-Kit und app-eigenen schema-konformen Katalog
  für Art.-15-Aggregation und temporären Admin-Vollzugriff ergänzt.

## 0.1.1

- Bestehender Entwicklungsstand des Data Protection Center bei Einführung
  dieses Changelogs; der aktuelle Funktionsumfang steht in `README.md`.
