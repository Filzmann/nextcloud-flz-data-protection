# Data Protection Center

`flz_data_protection` is a standalone Nextcloud app for provider-based
personal-data access reports, visible provider coverage and later retention
coordination.

The app never reads or modifies another app's tables, private configuration,
entities, files or AppData directly. Data-owning apps expose an explicitly
versioned provider projection. Missing, incompatible or failing providers
remain visible and are not replaced by heuristic fallback access.

## Current development scope

- public version-1 subject, descriptor, request, page and provider types;
- duplicate- and version-safe provider registry;
- lazy provider discovery through a typed Nextcloud event;
- fixed-snapshot aggregation with per-provider failure isolation;
- explicit expected-provider coverage with visible `missing` states;
- provider-scoped opaque cursors and a reusable technical contract-test kit;
- protected Self-Service report API whose subject is derived exclusively from
  the authenticated Nextcloud session;
- accessible transient Self-Service report UI with visible provider and
  completeness states and text-only rendering of provider values.
- public V1 retention-preview contract with lazy discovery, provider failure
  isolation and `REVIEW` as the only supported action;
- a transient operational REVIEW dashboard for configured privacy-review
  groups. A versioned install/upgrade migration creates the canonical
  `Datenschutzbeauftragte` group idempotently through native Nextcloud group
  management without assigning members. Native Nextcloud administration alone
  grants no fachlich access; only current group members may activate or revoke
  app-local per-admin access for at most 24 hours. The controls remain outside
  the technical admin settings, and the actual interval remains auditable;
- a protected-path notice shown only to native administrators without an
  active grant. It links to the app-local grant controls only when the same
  account is also a member of `Datenschutzbeauftragte`; ordinary and otherwise
  unauthorized accounts receive neither the administrative state nor the
  link;
- a public PermissionProvider that distinguishes self-service, REVIEW and
  technical configuration, plus a PersonalDataProvider for the subject's own
  admin-access and DPO configuration audit references without exposing other
  admin identifiers;
- an optional, append-only customer documentation area for closed German legal
  profiles. It remains DPO-protected and deliberately has no influence on
  retention execution. Legal-basis, agreement, DPO/BR confirmation and
  customer evidence stay outside the technical activation contract;
- a public V2 DELETE-provider contract and hourly coordinator. Execution is
  disabled on fresh install and can be switched on or off only by native
  Nextcloud administration. The activation is revisioned, admits exactly the
  recommended booking (`P1Y`) and temporary-admin-history (`P6M`) policies,
  and requires current technical backup and restore verification timestamps.
  Holds, policy/version/time integrity, provider compatibility, atomic
  provider execution and concurrency remain fail-closed. No customer legal
  source, BV reference, DPO/BR confirmation or legal evidence is requested by
  or blocks this activation;
- a public additive V1 `ProcessingMetadataProvider` contract with a
  duplicate- and version-safe lazy registry, strict app/catalog ownership,
  failure isolation and a reusable contract-test kit;
- a neutral public V1 risk-scope authorization query. Customer-local policy
  revisions remain private in this app and only current members of
  `Datenschutzbeauftragte` may configure them through the protected API. A
  consumer receives only `authorized`, `denied`, `incompatible` or
  `unanswered`; missing, disabled, future, expired, corrupted and incompatible
  states fail closed. The first closed scope guards the future Filzmann Raumplaner
  secretariat workflow for foreign bookings without enabling that mutation
  workflow itself;
- the app-owned processing catalog
  [`resources/privacy-processing.json`](resources/privacy-processing.json)
  for Art.-15 aggregation, temporary admin full access and the separate
  profile-revision audit. Open legal basis, business ownership, retention and
  backup decisions remain explicitly `PRIVACY-DECISION-REQUIRED` rather than
  receiving technical defaults.

The provider APIs remain pre-release and are not yet approved for external
releases. `flzroom` is the first V2 DELETE-provider consumer; the Permission
Matrix, Filzmann Raumplaner and Filzmann Urlaubsplanung continue to use the standalone V1
retention-preview registry. V1 discovery remains lazy, duplicate- and
version-safe, and V1 intentionally exposes no execution method. V2 providers
keep deletion inside their own repositories and receive only their approved
policy request. The center never reads or deletes foreign storage directly.
The app persists its own security-relevant temporary-admin history, optional
profile documentation, technical activation revisions and non-personal
customer-local risk-scope revisions. The recommended six-month admin-history
and one-year booking policies are recalculated from the original domain
trigger; deletion leaves no residual person statistics.

The initial consumer sequence started with `flzroom`. Provider coverage of an
installed workspace is discovered at runtime and is never inferred from that
pilot history. Remaining app-local work is tracked in [ROADMAP.md](ROADMAP.md).

## Tests

```bash
php tests/run.php
node tests/run-js.mjs
```

## Dokumentation

- [Architektur](docs/architecture.md)
- [Manuelle Abnahme](docs/manual-acceptance.md)
- [Roadmap](ROADMAP.md)
- [Changelog](CHANGELOG.md)
- [Arbeitsregeln](AGENTS.md)
