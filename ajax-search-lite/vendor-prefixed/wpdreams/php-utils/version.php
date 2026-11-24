<?php
/**
 * Single source of truth for this package's version. Returned as a plain string so it can be read
 * without autoloading any class (the shared-library loader compares candidate copies this way).
 * Bump on every release that changes the public API.
 *
 * 1.0.1 — add GPL-2.0-or-later LICENSE file (WordPress.org g1 compliance); no API change.
 * 1.0.2 — security: Str::anyToString() no longer instantiates objects from serialized input
 *         (allowed_classes => false), closing an unauthenticated PHP Object Injection vector.
 * 1.0.3 — security: Html::escCSS() leaves no "</" in the CSS, so custom CSS printed inside <style> can't end
 *         the element (the single-pass tag removal could be rebuilt or bypassed).
 * 1.0.4 — security: BFI_Thumb_1_3::thumb() only reads a file the URL names inside the uploads or the theme
 *         folder (realpath): a "../" in an image URL could copy any readable image on the server to a
 *         public URL under uploads/bfi_thumb.
 * 1.0.5 — Arg::positiveInt(): a positive integer id from a value code we do not control (hook arguments,
 *         filter results), or null; decides the same way on every PHP version
 *         (ajax-search-pro-development#524).
 * 1.0.6 — values from filters, the database and broken posts no longer end the request with a TypeError
 *         (wpdreams/ajax-search-pro#523): Post::dealWithShortcodes(), Post::parseImage(),
 *         Archive::getCurrentArchiveURL(), FileManager::listFiles() / listDirectoriesRecursively() /
 *         safeListDirectories(), Taxonomy::getTermsList(), WooCommerce::formattedPriceWithCurrency().
 *         Post::parseImage() tries the next image source again when one finds nothing (the move from
 *         Ajax Search Pro in 1.0.0 made a source with no image end the search).
 * 1.0.7 — Str::truncateAtWord() and MB::strrpos(). Str::contextFind() / getContext() cut through
 *         truncateAtWord() instead of wd_substr_at_word(), a global only Ajax Search Pro and Lite
 *         define, so they no longer end in a fatal error without them (#24). Text with no space
 *         to cut at (CJK, a long URL) keeps its first characters instead of becoming " ...". A
 *         length that is not a number no longer cuts at 0 or throws: no cut, or the default limit.
 */
return '1.0.7';
