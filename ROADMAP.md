# Roadmap – Datenschutz-Center

Diese Datei enthält ausschließlich offene Arbeit, zurückgestellte Vorhaben
und Freigabegates. Der aktuelle Funktionsumfang steht in `README.md`,
erledigte Änderungen in `CHANGELOG.md` und geltende Architektur in
`docs/architecture.md`.

## Nextcloud compatibility gate

### FLZ-DP-NC-COMPAT – RC-Kompatibilität und Zukunftsobergrenze nachweisen

Die `min-version` muss beim Release Candidate die aktuelle, autoritativ
ermittelte openDesk-Nextcloud-Hauptversion abdecken. Erst beim Erstellen eines
veröffentlichungsfähigen RC wird jeder deklarierte Major lückenlos geprüft:
Fresh Install/Upgrade, DI, Provider-Discovery und Versionsverhandlung,
Self-Service, Retention-Vorschau, temporäres Admin-Audit, Assets und sichtbare
Oberfläche. `max-version` folgt ausschließlich der höchsten lückenlos
nachgewiesenen Major aus offiziellen, gepinnten Nextcloud-Git-Quellen; eine
offiziell benannte und testbare künftige Major (z. B. NC36) wird dabei geprüft.
Der regelmäßige Check der neuesten veröffentlichten Entwicklungsruntime ist
davon getrennt und ersetzt keinen RC-Nachweis. Fehlende, deaktivierte und
inkompatible Provider bleiben explizite Zustände statt verborgener
Kompatibilitätsbehauptungen.

## Next implementation gates

1. **Release gate – development cleanup:** before the next release, inventory
   all obsolete development `privacy-officer` groups, accounts, fixtures and
   configuration remnants. Safely remove every unused remnant rather than
   retaining legacy compatibility; the project is not productive and has no
   historical state to preserve. This is a separately authorized cleanup task:
   no deletion occurs through this roadmap entry.
2. Implement the decided lifecycle for technical activation revisions: retain
   them for 24 months after supersession, expose only the subject's own
   operator revisions through Art. 15 and add their automatic deletion without
   preserving person statistics. This is independent of provider-domain
   booking and admin-history deletion.
3. Keep optional customer legal-profile documentation separate from technical
   activation. Before deleting that documentation history, decide its own
   retention, Art.-15, hold and backup treatment; it never blocks or enables
   V2 execution.
