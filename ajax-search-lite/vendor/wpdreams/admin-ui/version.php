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
 * 1.4.0 — per-plugin REST namespace (config + routes); SharedLibraryNotices $GLOBALS guard.
 * 1.4.1 — add GPL-2.0-or-later LICENSE file (WordPress.org g1 compliance); no API change.
 * 1.5.0 — Input/Textarea: auto-expanding textarea component (grows with content up to maxHeight).
 * 1.6.0 — PostMetaValuesRoute (distinct meta values suggester) + usePostMetaValues hook and
 *          CustomFieldValueSelect creatable multi-select over it. Input/Number float support
 *          (optional `float` prop parses/clamps with parseFloat instead of parseInt).
 * 1.7.0 — MultiSelectOption: whitelisted string-array option (fixed-vocabulary multi-choice,
 *          deduplicated, default fallback when nothing valid remains).
 * 1.7.1 — MenuItem: clicks on the already-active item and submenu toggles no longer run or
 *          clear confirmBeforeSwap (silently disarmed consumers' unsaved-changes guards, #29).
 * 1.8.0 — MenuItem confirmBeforeSwap accepts { message, unless(targetId) } to skip the prompt
 *          for targets the consumer marks safe; the plain string form is unchanged.
 */
return '1.8.0';
