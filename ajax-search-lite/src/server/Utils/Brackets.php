<?php

namespace WPDRMS\ASL\Utils;

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

/**
 * Square brackets of printed text as hex character references.
 *
 * Text a visitor controls (a search phrase) can be printed into HTML that WordPress scans for shortcodes
 * once more: a block's output inside post content or a block widget, a page builder module, a widget
 * filter. As `&#x5B;` and `&#x5D;` the browser shows, and the DOM holds, the same brackets, but no
 * shortcode pass sees a tag in them.
 *
 * Hex, never decimal: WordPress turns `&#91;` and `&#93;` back into brackets at the end of every
 * shortcode pass (unescape_invalid_shortcodes()), and the next pass would see the tag.
 */
class Brackets {
	/**
	 * For text in HTML, a text node or an attribute value: after escaping it, or before esc_attr() and
	 * esc_html(), which never encode a character reference again. Never for text inside a <script>, a
	 * <style> or raw JSON, where the browser does not decode references.
	 *
	 * @param string $text escaped text, or text esc_attr() / esc_html() escape next
	 */
	public static function inert( string $text ): string {
		return strtr(
			$text,
			array(
				'[' => '&#x5B;',
				']' => '&#x5D;',
			)
		);
	}
}
