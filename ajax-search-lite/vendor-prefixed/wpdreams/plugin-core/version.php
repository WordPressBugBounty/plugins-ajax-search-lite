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
 * 1.5.0 — License client (AjaxSearchLite\Vendor\WPDRMS\PluginCore\License): LicenseManager with LicenseConfig,
 *         LicenseStatement, LicenseReply, LicenseApi, LicenseStore, LicenseException and
 *         PublicKeys, for the licence server's versioned routes.
 * 1.6.0 — Connect activation (plugin-core #22): LicenseConnect (available, start, returned, finish,
 *         resumable, resume), LicenseManager::activateByCode(), LicenseApi::connectStart() /
 *         connectFinish(), LicenseReply codes `pending` / `invalid_code` with request(), expiresIn()
 *         and key(), LicenseConfig `account_url` / `return_url`, ConnectAddress, and the
 *         LicenseException codes `connect_unavailable`, `connect_expired`, `connect_denied`.
 * 1.7.0 — LicenseApi sends the WordPress and PHP versions (`wp`, `php`) on activate and check, as
 *         the licence server keeps them (LicenseApi::telemetry()), for its version report.
 * 1.8.0 — TypedPropertyAssignmentTrait (wpdreams/ajax-search-pro#523): ObjectAsArrayTrait (constructor and
 *         offsetSet()) and ORM Model hydration (create, find, findAll, findBy) no longer end the request with a
 *         TypeError when a filter, a customer snippet or a database row hands a typed property a value it
 *         refuses. PHP's own coercion is kept; null takes the declared default, a scalar on an array property
 *         is wrapped, anything else is skipped with _doing_it_wrong(). offsetSet() can now set a property
 *         that holds null (it tested isset()).
 * 1.9.0 — Trial licences (wpdreams/ajax-search-pro#547): License\Trial (state(), endsAt(), key(), check(),
 *         checkOnAdminPage(), register(), adoptV1Stamp(), optionName(), cronHooks(), reset()), LicenseConfig
 *         `plugin_basename` with pluginBasename(), LicenseStatement::trialEndsAt(), LicenseReply::trial().
 *         LicenseManager keeps the trial record on its verdicts; its public API is unchanged. A paid licence is
 *         never stopped: no stamp is taken in while the licence record holds a statement accepted here and now
 *         without `trial_ends`, Trial::state() is `none` meanwhile and Trial::check() clears the record without
 *         asking. LicenseStatement::signedByOneOf() became public, `@internal` (shared with the trial record).
 */
return '1.9.0';
