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

1. Keep the implemented, versioned six-month `REVIEW` projection from the
   actual end of the temporary app-local admin-access grant aligned with the
   processing catalog. Implement `DELETE` without residual statistics only
   after the DP-07 execution gates below are complete; restore must re-evaluate
   the original end without reactivating access, and legal or privacy holds
   must block deletion until an audited DPO-only release.
2. Keep the automatically provisioned Nextcloud group
   `Datenschutzbeauftragte` as the exclusive role for fachliche
   Datenschutzkonfiguration, remove any configuration path based only on native
   administration, validate membership responsibility in the target
   organization and never treat the DDEV-only name
   `privacy-officer` as a product role;
3. **Release gate – development cleanup:** before the next release, inventory
   all obsolete development `privacy-officer` groups, accounts, fixtures and
   configuration remnants. Safely remove every unused remnant rather than
   retaining legacy compatibility; the project is not productive and has no
   historical state to preserve. This is a separately authorized cleanup task:
   no deletion occurs through this roadmap entry.
4. Keep third-person content and security secrets inside each provider's
   domain projection;
5. Implement the approved app-local DP-07 pilot contract from
   `docs/architecture.md` only in a separately authorized test-driven task.
   The first pilot is limited to `fdp_admin_access`; the implemented V1
   preview remains `REVIEW`-only and receives no `execute()` method. Before
   any activation, keep the implemented closed German customer-profile model
   without free legal-basis input green. The additive, DPO-only and revisioned
   instance configuration is implemented; its empty, invalid and stale states
   remain `REVIEW` and it deliberately provides no execution path. Keep every
   customer's profile selection, agreement or balancing reference, scope, effective date, review
   date and DPO confirmation exclusively in app-instance configuration; the
   distributable package must ship with no customer selection. Independently
   decide the legal basis, retention trigger and duration, holds, erasure and
   backup treatment of the separate profile-revision audit before adding any
   deletion path for those revisions; it does not inherit the six-month
   admin-access-history policy. Also prove each instance's configured real
   backup retention within at most 30
   regular days plus at most five technical buffer days and prove the
   fail-closed restore barrier. A policy selection or entered target duration
   is not operational evidence. The
   implementation must then cover the DPO-only policy/hold boundary, fixed
   policy/evaluation snapshot, retroactive recalculation from the original
   grant end, stable ordering, per-record atomicity, concurrency, idempotency,
   retry and data-minimizing 30-day failure evidence with all positive and
   negative tests listed in the architecture. Execution is automatic without
   manual per-record release only after this gate is green;
6. Keep the DPO-managed lifecycle source separate from that execution pilot.
   A DPO retention case may later record a scoped, corrected lifecycle fact,
   but must never claim `EMPLOYMENT_ENDED`; the admin-history pilot continues
   to use the actual grant end. Before persisting such cases, decide their own
   access, Art.-15, rectification, hold and retention treatment. A future HR
   or payroll employment-end source needs a separate authorized, versioned
   provider contract and may not overwrite DPO cases.
