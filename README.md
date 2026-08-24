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
- a transient operational REVIEW dashboard. Native Nextcloud admins can read
  it by default after installation, but that read right is independently
  configurable and should be reassessed after a dedicated privacy-review
  group has been established.

The provider APIs remain pre-release and are not yet approved for external
releases. `adroom` is the first personal-data pilot consumer; the Permission
Matrix is the first standalone retention-preview consumer. No report
persistence, retention execution, data deletion, anonymization or audit
persistence is part of version `0.1.0`.

## Tests

```bash
php tests/run.php
node tests/run-js.mjs
```
