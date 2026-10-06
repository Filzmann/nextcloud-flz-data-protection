# Roadmap – Datenschutz-Center

Diese Datei enthält ausschließlich offene Arbeit, zurückgestellte Vorhaben
und Freigabegates. Der aktuelle Funktionsumfang steht in `README.md`,
erledigte Änderungen in `CHANGELOG.md` und geltende Architektur in
`docs/architecture.md`.

## Nextcloud compatibility gate

### FDP-NC-COMPAT – RC-Kompatibilität und Zukunftsobergrenze nachweisen

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

1. Complete runtime acceptance of the implemented V2 DELETE pilot on the
   supported real database matrix. Verify fresh installation, upgrade,
   background-job restart, concurrent provider execution, rollback before a
   candidate commit and restore quarantine without weakening the unit and
   contract tests. The runtime proof must also show that missing, disabled,
   future, stale or invalid technical activation remains fail-closed while no
   legal-basis, agreement, DPO/works-council or customer-evidence field is
   required. This needs a separately authorized DDEV/runtime run.
2. Keep the automatically provisioned Nextcloud group
   `Datenschutzbeauftragte` as the exclusive role for fachliche
   Datenschutzkonfiguration. Technical V2 DELETE activation is deliberately a
   separate native-administration responsibility and must not expose fachliche
   report data or record-level deletion controls. Never treat the DDEV-only
   name `privacy-officer` as a product role;
3. **Release gate – development cleanup:** before the next release, inventory
   all obsolete development `privacy-officer` groups, accounts, fixtures and
   configuration remnants. Safely remove every unused remnant rather than
   retaining legacy compatibility; the project is not productive and has no
   historical state to preserve. This is a separately authorized cleanup task:
   no deletion occurs through this roadmap entry.
4. Keep third-person content and security secrets inside each provider's
   domain projection;
5. Implement the decided lifecycle for technical activation revisions: retain
   them for 24 months after supersession, expose only the subject's own
   operator revisions through Art. 15 and add their automatic deletion without
   preserving person statistics. This is independent of provider-domain
   booking and admin-history deletion.
6. Keep optional customer legal-profile documentation separate from technical
   activation. Before deleting that documentation history, decide its own
   retention, Art.-15, hold and backup treatment; it never blocks or enables
   V2 execution.
7. Separate the session-bound Art. 15 self-service from fachliche DPO
   controls in the UI. Personal Art. 15 information is exposed only through
   the Nextcloud personal `Datenschutz` settings entry. DPO report, policy,
   admin-grant and retention functions remain in the app menu and are split
   into coherent, accessible sections. This navigation change must preserve
   the existing server-side role separation and needs route, direct-access,
   keyboard, focus and responsive-layout checks.
