<?php
/**
 * Single source of truth for this package's version. Returned as a plain string so it can be read
 * without autoloading any class (the shared-library loader compares candidate copies this way).
 * Bump on every release that changes the public API.
 *
 * 1.0.1 — add GPL-2.0-or-later LICENSE file (WordPress.org g1 compliance); no API change.
 * 1.0.2 — security: Str::anyToString() no longer instantiates objects from serialized input
 *         (allowed_classes => false), closing an unauthenticated PHP Object Injection vector.
 */
return '1.0.2';
