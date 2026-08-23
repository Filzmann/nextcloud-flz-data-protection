# Privacy implementation plan

The first supported subject type is `nextcloud-user`; its identifier is the
authenticated Nextcloud UID. Self-service must construct that reference on
the server. External applicants or employees without a Nextcloud account are
not mapped to this subject type.

The app initially stores no personal report content. Before persistence,
exports, admin access or retention execution are implemented, dedicated
permission, storage, audit, lifecycle and deletion decisions are required.

Next implementation gates:

1. characterize the LocalBase pilot without making it a runtime dependency;
2. stabilize provider descriptor, status, metadata, cursor and version
   semantics through a neutral provider/consumer contract-test kit;
3. implement Nextcloud event-based lazy registration and an expected-provider
   coverage model;
4. bind Self-Service to the authenticated session and prove manipulated
   subjects cannot be queried;
5. decide the dedicated admin privacy role, including whether Nextcloud admin
   status alone remains insufficient;
6. keep third-person content and security secrets inside the provider's
   domain projection;
7. begin retention with preview and `REVIEW` only; no destructive action
   before policy, lifecycle, concurrency and rollback approval.

