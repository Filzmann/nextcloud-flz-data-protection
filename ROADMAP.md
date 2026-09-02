# Roadmap – Datenschutz-Center

Diese Datei enthält ausschließlich offene Arbeit, zurückgestellte Vorhaben
und Freigabegates. Der aktuelle Funktionsumfang steht in `README.md`,
erledigte Änderungen in `CHANGELOG.md` und geltende Architektur in
`docs/architecture.md`.

## Nextcloud compatibility gate

### FDP-NC-COMPAT – prove the declared 29–35 range and future majors

`info.xml` already includes Nextcloud 33. Before the next release, every
declared major must pass a contiguous matrix covering fresh install/upgrade,
DI, provider discovery and version negotiation, self-service, retention
preview, temporary-admin audit, assets, and the visible UI. Raise the upper
bound only from app-local `verify-nextcloud-future-compatibility` evidence;
missing, disabled, and incompatible providers remain explicit states rather
than hidden compatibility claims.

## Next implementation gates

1. Decide a retention period and preview policy for the temporary app-local
   admin-access audit before any retention execution is introduced.
2. Validate the configured dedicated review groups in the target organization
   and keep temporary admin grants exceptional;
3. Keep third-person content and security secrets inside each provider's
   domain projection;
4. Keep retention execution blocked until policy, lifecycle, concurrency and
   rollback approval; the implemented V1 preview contract has no execution
   method;
5. Add or migrate consumers only through separately approved app-local work.
   The physically missing, disabled and genuinely incompatible runtime states
   are already covered by the Root compatibility check and local contract
   tests; every new consumer still needs its own start and failure proof.
