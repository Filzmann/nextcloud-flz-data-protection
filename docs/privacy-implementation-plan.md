# Privacy implementation plan

The first supported subject type is `nextcloud-user`; its identifier is the
authenticated Nextcloud UID. Self-service must construct that reference on
the server. External applicants or employees without a Nextcloud account are
not mapped to this subject type.

The app stores no personal report content. Its only own personal records are
the security-relevant, app-local temporary admin-access intervals. Further
persistence, exports or retention execution require dedicated permission,
storage, audit, lifecycle and deletion decisions.

Implemented contract foundation:

- strict V1 descriptor and status validation;
- provider-scoped opaque cursors and provider page limits;
- typed, lazy Nextcloud event registration with duplicate and version
  rejection;
- explicit expected-provider coverage without an implicit completeness claim;
- a reusable technical `PersonalDataProviderContractTestKit`;
- a parameterless Self-Service report API that derives the `nextcloud-user`
  subject exclusively from `OCP\IUserSession`, uses native Nextcloud
  localization and rejects anonymous calls before provider discovery;
- an accessible transient report UI that renders provider values as text,
  exposes missing, partial and failed states and stores no report copy;
- the first real consumer, `adroom`, registered through the public V1 event.
  Its provider preserves room, purpose and time context while replacing the
  free booking title with a neutral placeholder because it may contain data
  about other people;
- the second real consumer, `filzmann_permission_matrix`, registered through
  the same optional V1 event. Its subject-bound read-only projection exposes
  own snapshot-creation, export-metadata and audit references while omitting
  snapshot/export content, filenames, free audit details and other users;
- local Nextcloud runtime verification with the privacy app enabled and
  disabled, including the authenticated Self-Service endpoint and static
  assets. The initially disabled app state was restored afterwards.
- public V1 retention-preview types, lazy registry and failure-isolated
  aggregation with no execution method and `REVIEW` as the only action;
- a protected transient REVIEW dashboard. Configured review groups have
  explicit access; native Nextcloud admins require a per-admin app-local grant
  that expires after at most 24 hours. Technical settings access remains
  separate and Nextcloud-native;
- app-local PersonalDataProvider and PermissionProvider coverage for the
  temporary grant audit, self-service, REVIEW and technical configuration;
- `filzmann_permission_matrix` as the first standalone preview provider for
  due export metadata and audit records, using separate configurable periods
  of 180 days by default and selecting no UIDs or free details.

Next implementation gates:

1. automate integration cases for a physically missing and a genuinely
   incompatible privacy app; version rejection is already covered at the
   contract level and the disabled runtime state is verified;
2. decide and explicitly approve each next single consumer migration;
3. validate the configured dedicated review groups in the target organization
   and keep temporary admin grants exceptional;
4. keep third-person content and security secrets inside each provider's
   domain projection;
5. keep retention execution blocked until policy, lifecycle, concurrency and
   rollback approval; the implemented V1 preview contract has no execution
   method.
