<?php
/**
 * Single source of truth for this package's version. Returned as a plain string so it can be read
 * without autoloading any class (the shared-library loader compares candidate copies this way).
 * Bump on every release that changes the public API.
 *
 * 1.0.0 — added PriorityGroupsOption `conditions` (flexible gate) and `boosts` (prioritized boosts).
 * 1.1.0 — CustomFieldSelect `creatable` prop + CreatableSelectBase (manually addable meta keys).
 * 1.2.0 — PostTypesRoute (options/post-types/get) + usePostTypes hook; PostTypeSelect lists all registered post types, including show_in_rest=false.
 * 1.3.0 — SharedLibraryNotices (PHP helper + React component in TopBar) surfaces plugin-core's shared-library version-conflict notices on the modern admin pages, which suppress native admin_notices.
 */
return '1.3.0';
