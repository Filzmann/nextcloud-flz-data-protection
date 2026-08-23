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
- duplicate-safe provider registry;
- fixed-snapshot aggregation with per-provider failure isolation;
- neutral standalone Nextcloud entry explaining that no complete report is
  available before providers are registered.

The API is pre-release and not yet approved for external consumers. No
retention execution, data deletion, anonymization, audit persistence or real
consumer migration is part of version `0.1.0`.

## Tests

```bash
php tests/run.php
node tests/run-js.mjs
```

