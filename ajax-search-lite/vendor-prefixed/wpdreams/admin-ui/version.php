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
 * 1.15.1 — Popover never leaves the viewport: a panel that fits on neither side of its anchor is
 *          moved up until it does, its height is capped to the viewport and its body scrolls under
 *          the header. LinkedBoxControl's linked sides are ONE input ("All sides"); linking gives
 *          every side the first side's value, or the first side that holds a number (`linkValue()`),
 *          and sides that differ are never shown as one. No PHP or prop change.
 * 1.16.0 — Modal is a dialog (#535). While open its content is role="dialog" + aria-modal (a
 *          consumer's own role and aria-* win), named by its first h1–h4 (a generated id is
 *          never one the page already uses) unless an aria-label, aria-labelledby or the new
 *          `labelledBy` names it; while closed the always-mounted overlay is inert + aria-hidden
 *          (its controls leave the Tab order, an autoFocus child cannot take focus at mount).
 *          New optional `onClose` (Escape; falls back to `onOverlayClick`, no-op without either)
 *          and `initialFocus`. On open focus goes to an autoFocus child, else `initialFocus`,
 *          else the dialog itself (never its first button: a held Enter must not confirm a
 *          Delete); Tab from there goes to the first Tab stop, Tab / Shift+Tab wrap at the
 *          edges, and focus returns to the opener on close. Without layout (jsdom) the trap
 *          skips its "is it rendered?" checks. Keyboard contract: ONE native keydown listener
 *          on the overlay handles Escape and the trap's edges and stops them there, so React
 *          bubble-phase handlers inside an open Modal (and native listeners on its ancestors)
 *          never see Escape or an edge Tab; a Tab between two inner elements goes on as usual.
 *          A child claims Escape with `onKeyDownCapture` + `e.preventDefault()` (an element with
 *          an open popup — aria-expanded combobox / aria-haspopup — keeps its Escape too).
 *          Popover's Escape now calls preventDefault(), so a Popover inside a Modal closes alone.
 *          No PHP change.
 * 1.17.0 — Google font weights: googleFontsCssUrl() / ensureGoogleFontsLoaded() take
 *          `{ family, axes }` beside plain names (`family=Lato:wght@400;700`), and the loader
 *          remembers a family WITH its axes, so a family loaded at its regular face is
 *          requested again when it needs weights. The caller works the axes out from its own
 *          catalog. Plain names behave byte-identically. No PHP change.
 * 1.18.0 — DropDownMenu closes when an item is chosen (ASP dev #438): a click on a button,
 *          link or menuitem in the menu runs the item's handler, then closes it (new prop
 *          `closeOnSelect`, default true; a click on non-actionable content keeps it open).
 *          Focus returns to the trigger after a close from inside unless the item moved it;
 *          the trigger is a keyboard button (role=button, Tab stop, Enter/Space, aria-expanded)
 *          and Escape closes the menu.
 *          Repeater/useRepeater (ASP dev #466): an editable row list with STABLE row keys kept
 *          beside plain `T[]` values (or a domain id through `getKey`), remove / update by key,
 *          `move`, a per-row `isNew` flag kept by key, and focus into a new row's first field
 *          (`row.ref` on the row's root, `data-repeater-autofocus` to pick the field);
 *          RepeaterLogicSwitch / RepeaterConnector are the optional AND/OR chrome.
 *          CustomFieldRules and TaxonomyExclusionRules are built on it (same markup; a new rule's
 *          first field takes focus), their rows take an optional `rowRef`, and ColorControl's
 *          palette editor removes by id and focuses a new colour's name. No PHP change.
 * 1.19.0 — PostSelect takes `exclude` (post ids the search never offers; selected ones stay
 *          shown) and `ariaLabel` (the combobox's accessible name). Without them it renders
 *          as before. No PHP change.
 * 1.20.0 — stored option data of a shape the options refuse no longer ends the request
 *          (wpdreams/ajax-search-pro#523). AbstractOptionData::get() decodes a JSON string as an
 *          array (never a stdClass) and an object as its properties; a bare value still throws a TypeError,
 *          on purpose, so consumers such as site-core fail closed on it. AbstractOption gets
 *          argList() / argInt() / argString() / itemsOnly(), used by Int, IntArray, DirectoryList,
 *          Border, BoxShadow, Select, MultiSelect and the rule/item/group list options. PostsRoute
 *          takes ?ids[]= as well as ?ids=1,2, and every shared route answers a \Throwable with an
 *          error response instead of a 500.
 * 1.20.1 — TermSelect draws its chips from the term ids on every render (from `terms`, or from
 *          local state seeded by `defaultTerms`), so the saved terms show whichever request answers
 *          first, and a changed `terms` (a taxonomy switch, an undo) replaces them. It used to copy
 *          them once and miss them when the selected terms answered before the term list (ASP #649).
 * 1.21.0 — theme colours and fonts (ASP #534). ColorControl takes `themeColors` and adds a
 *          "Theme · <name>" section of the active theme's palette to its popover, which is then
 *          titled "Colors" and gives Global Colors their own heading (Input/ThemeColors: ThemeColorsButton
 *          and the link helpers composeThemeColorVar / themeColorRef); FontFamilySelect takes
 *          `themeFonts` and offers them as its first group, "Theme · <name>" (composeThemeFontVar /
 *          themeFontSlug). Without the new props both render as before. No PHP change.
 */
return '1.21.0';
