# Changelog

## Unreleased

- Additive, revisionsgeführte Instanzkonfiguration für die geschlossenen
  deutschen Rechtsprofile und die Backup-/Restore-Nachweise ergänzt. Nur
  `Datenschutzbeauftragte` dürfen speichern; unvollständige, manipulierte oder
  fällige Nachweise bleiben `REVIEW`, und es entsteht kein Job- oder Löschpfad.
  Das Rechteinventar führt die DPO-Capability explizit; eigene Revisionen
  werden subjectgebunden paginiert, und ihre separate Aufbewahrung bleibt als
  Datenschutzentscheidung offen.
- Die kanonische Nextcloud-Gruppe `Datenschutzbeauftragte` wird bei
  Installation beziehungsweise Upgrade idempotent über die native
  Gruppenverwaltung angelegt, ohne bestehende Gruppen oder Mitglieder zu ändern.
- Reale Nextcloud-34-Lifecycle-Matrix für AD Raumplaner und AD Urlaub ergänzt: alter LocalBase-Pilot, gemeinsames Update auf den Standalone-V1-Vertrag, deaktivierte und entfernte Privacy-App, Neuinstallation sowie vorwärtsversionierter Rollback sind grün; Provider-Discovery bleibt in jedem Zustand explizit.
- Öffentlichen V1-Retention-Previewvertrag um feste Bewertungszeitpunkte,
  opake seitenweise Fortsetzungen, isolierte Providerfehler und robuste
  Nachladezustände ergänzt; AD Raumplaner und AD Urlaub vom LocalBase-Pilot
  als eigenständige Consumer migriert.
- App-lokale Adminfreigabe an die feste Gruppe `Datenschutzbeauftragte`
  gebunden, aus der technischen Administration in den geschützten App-Einstieg
  verschoben und um rollenabhängige Eintrittsmeldung, Direktlink sowie
  Allow-, Deny- und Manipulationsnachweise ergänzt.
- Sechsmonats-Standard der eigenen Adminfreigabehistorie ausschließlich für
  `Datenschutzbeauftragte` versioniert konfigurierbar gemacht und als
  datenminimierte, rückwirkend vom tatsächlichen Ende berechnete
  `REVIEW`-Vorschau registriert; V1 besitzt weiterhin keinen Ausführungspfad.
- Dokumentations- und Steuerungsstruktur vereinheitlicht.
- Additiven öffentlichen V1-Processing-Metadata-Providervertrag, lazy Registry,
  Fehlerisolation, Contract-Test-Kit und app-eigenen schema-konformen Katalog
  für Art.-15-Aggregation und temporären Admin-Vollzugriff ergänzt.

## 0.1.1

- Bestehender Entwicklungsstand des Data Protection Center bei Einführung
  dieses Changelogs; der aktuelle Funktionsumfang steht in `README.md`.
