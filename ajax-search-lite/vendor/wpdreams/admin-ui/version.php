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
 * 1.9.0 — DropDownMenu renders its menu in a portal (into #wdo, position:fixed at
 *          the trigger with the existing flip logic) so it is no longer clipped by
 *          overflow/height-constrained ancestors; also closes on outside-click /
 *          scroll / resize. Public props unchanged.
 * 1.10.0 — DimensionControl, LinkedBoxControl, Popover, FontFamilySelect (Elementor-style results-builder controls).
 * 1.11.0 — ColorControl + Global Colors popover (pick/manage a site-wide named palette; var(--asp-gc-<id>) bindings, checkerboard unset swatch).
 * 1.12.0 — DimensionControl/LinkedBoxControl/ColorControl gain optional `inherited`
 *          (italic-mutes the value input(s) to show an inherited/fallback value,
 *          box stays fully interactive); ColorControl gains an optional `device`
 *          slot beside its label (mirrors DimensionControl/LinkedBoxControl).
 * 1.12.1 — Popover `anchorRef` and ColorControl's GlobalColorsPopover `anchorRef`
 *          widen to `RefObject<HTMLElement | null>`: React 19's types make
 *          `useRef<T>( null )` produce `T | null`, so the previous declaration
 *          was unsatisfiable by any caller. Widening only; both already
 *          null-checked `.current`.
 * 1.13.0 — OptionFactory::register( $type, $class ) adds an option type at run time (kept on
 *          the instance: resetInstance() forgets it; the class must be instantiable and
 *          implement Option and JsonSerializable).
 *          Layout/PageShell (tab pills), Layout/TwoColumn (main column + sticky rail) and
 *          Layout/Panel (card with an optional icon-chip header), promoted from SnipCraft.
 *          PageShell's pill hover and press styles (no underline, the active pill's text
 *          colour) apply without a mouse too: arbitrary [&:hover]/[&:active] variants.
 *          Input/SecretInput: a write-only secret field (keep / replace / clear).
 *          OnOff gains `label` (aria-label), `disabled` (aria-disabled), `describedBy` and
 *          `invalid`; Space no longer scrolls the page.
 * 1.13.1 — Menu: arriving at an id reveals it however it was reached (navigate(), goBack(), a
 *          hash change, setActiveId()), also in a sidebar whose tree depends on the active id;
 *          only the active item's branch opens, so a group the user collapsed stays collapsed;
 *          the hash-change handler no longer opens the previous id (#32). No API change.
 * 1.13.2 — DimensionControl's number box and LinkedBoxControl's four side boxes
 *          hide the browser spinner (`wdo:input-number`, as the shared Number),
 *          which took ~15px of a narrow box; DimensionControl's box widens from
 *          `w-14` to `w-[4.8em]` (em of its own font), so values such as
 *          `999`, `10000`, `-12.25` and `1000.5` are no longer cut off.
 *          Markup/CSS only; no PHP or prop change.
 * 1.14.0 — Accessibility (#40). Option gains `descriptionId` (the description's id, for a
 *          control's aria-describedby). SecretInput's field and buttons sit in a role="group"
 *          named by `label`, and a visually hidden status says "Will be cleared on save".
 *          Messages announces its toasts through two visually hidden live regions (status for
 *          a success, alert for an error) owned by the most recently mounted instance; toasts
 *          already shown when an instance takes over are not announced. Its Dismiss label is
 *          translated. OnOff ignores Enter/Space auto-repeat. PageShell's inactive pills use
 *          arbitrary [&:hover] variants, so their hover applies without a mouse. No PHP change.
 * 1.15.0 — DateTimeInput (native datetime-local with a Clear button and an optional site-timezone hint);
 *          option types `datetime` (DateTimeOption: UTC timestamp, toLocal()/fromLocal() in the site timezone)
 *          and `html` (HtmlOption: wp_kses_post'd, never rewritten by Str::anyToString).
 */
return '1.15.0';
