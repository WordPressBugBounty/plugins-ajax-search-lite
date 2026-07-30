<?php
/**
 * Single source of truth for this package's version. Returned as a plain string so it can be read
 * without autoloading any class (the shared-library loader compares candidate copies this way).
 * Bump on every release that changes the public API.
 *
 * 1.1.0 — AbstractRest::allowOnlyAdmins()/allowOnlyLoggedIn() gained loosen-only permission filters
 *         (`wpdrms/core/rest/allow_only_admins`, `wpdrms/core/rest/allow_only_logged_in`) that
 *         receive the WP_REST_Request, enabling scoped read-access overrides (e.g. demo mode).
 * 1.2.0 — Shared-library conflict notices now name the plugin shipping the loaded copy; added
 *         wpdrms_shared_get_conflicts() public accessor (conflicts enriched with source_slug /
 *         source_name) for surfacing the conflicts outside admin_notices (e.g. admin-ui).
 * 1.2.1 — wpdrms_shared_get_conflicts() collapses conflicts per library (keeps the strictest
 *         required version), so several plugins requiring the same library show one notice, not one
 *         per consuming plugin.
 * 1.3.0 — per-plugin REST namespace + token-derived auth filters; AbstractRest gains
 *         sanitizeNamespace(), vendorToken(), and $route_namespace; all admin-ui routes
 *         accept an optional $route_namespace defaulting to self::ROUTE_NAMESPACE.
 * 1.3.1 — add GPL-2.0-or-later LICENSE file (WordPress.org g1 compliance); no API change.
 * 1.4.0 — ORM Model gains findAll( $order_by, $order ) — the unbounded
 *         "fetch every row" counterpart of findBy() (plugin-core #18).
 */
return '1.4.0';
