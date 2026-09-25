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

1. Implement the decided six-month default from the actual end of the
   temporary app-local admin-access grant and `DELETE` without residual
   statistics. Re-evaluate the original end after restore without reactivating
   access; legal or privacy holds block deletion and only
   `Datenschutzbeauftragte` may lift them with an audited reason.
2. Enforce the dedicated Nextcloud group `Datenschutzbeauftragte` as the
   exclusive role for fachliche Datenschutzkonfiguration, remove any
   configuration path based only on native administration, validate the group
   in the target organization and never treat the DDEV-only name
   `privacy-officer` as a product role;
3. Keep third-person content and security secrets inside each provider's
   domain projection;
4. Keep retention execution blocked until the 24-month configuration audit
   with annual review, versioned policy and effective time, safe retroactive
   recalculation, ordering, atomicity, concurrency, idempotency, operational
   backup boundary, hold enforcement, audit completeness, automatic retries,
   data-minimizing DPO notification, 30-day technical failure evidence,
   rollback and provider/consumer behavior are approved and tested. Execution
   is later automatic without manual release; the implemented V1 preview
   contract still has no execution method;
