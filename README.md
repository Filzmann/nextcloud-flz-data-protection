# Data Protection Center

`filzmann_data_protection` is a standalone Nextcloud app for provider-based
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
  groups. Native Nextcloud administration alone grants no fachlich access;
  per-admin access can be activated app-locally for at most 24 hours and its
  actual interval remains auditable;
- a public PermissionProvider that distinguishes self-service, REVIEW and
  technical configuration, plus a PersonalDataProvider for the subject's own
  admin-access audit references without exposing other admin identifiers.
- a public additive V1 `ProcessingMetadataProvider` contract with a
  duplicate- and version-safe lazy registry, strict app/catalog ownership,
  failure isolation and a reusable contract-test kit;
- the app-owned processing catalog
  [`resources/privacy-processing.json`](resources/privacy-processing.json)
  for Art.-15 aggregation and temporary admin full access. Open legal basis,
  business ownership, retention and backup decisions remain explicitly
  `PRIVACY-DECISION-REQUIRED` rather than receiving technical defaults.

The provider APIs remain pre-release and are not yet approved for external
releases. `adroom` is the first personal-data pilot consumer; the Permission
Matrix is the first standalone retention-preview consumer. No report
persistence, retention execution, data deletion or anonymization is part of
version `0.1.1`; only the security-relevant temporary admin-access history is
persisted app-locally.

The initial consumer sequence started with `adroom`. Provider coverage of an
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
