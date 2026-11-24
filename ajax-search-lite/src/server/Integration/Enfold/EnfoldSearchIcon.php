<?php

namespace WPDRMS\ASL\Integration\Enfold;
\defined( 'ABSPATH' ) || exit; \defined( 'ASL_SCOPED' ) || ! \defined( 'ASL_PATH' ) || require_once ASL_PATH . 'vendor-prefixed/autoload.php';

use WPDRMS\ASL\Integration\IntegrationInterface;
use AjaxSearchLite\Vendor\WPDRMS\PluginCore\Traits\SingletonTrait;

if ( !defined('ABSPATH') ) {
	die('-1');
}

/**
 * Enfold: a click on the menu search icon's graphic opens the search box (#171, port of Ajax Search Pro #734).
 *
 * Enfold's header search box (where the plugin's search box sits when it replaces the theme's search form)
 * opens only when the click lands on the icon's link. Under a transparent header, Enfold's hover fade lifts
 * the icon's `<svg>` above the link, the click lands on the graphic and Enfold drops it: the search box
 * seems to open at random. The rule below lets every click through to the link.
 *
 * Printed whenever Enfold is the active template (child themes included), whatever form the search box
 * holds: the bug is Enfold's. A site can drop it with `wp_dequeue_style( 'asl-enfold-compat' )` after
 * `wp_enqueue_scripts`.
 */
class EnfoldSearchIcon implements IntegrationInterface {
	use SingletonTrait;

	public const HANDLE = 'asl-enfold-compat';

	public const CSS = '#top #menu-item-search > a svg { pointer-events: none; }';

	public function register(): void {
		add_action('wp_enqueue_scripts', array( $this, 'enqueue' ));
	}

	public function deregister(): void {
		remove_action('wp_enqueue_scripts', array( $this, 'enqueue' ));
	}

	/**
	 * @hook wp_enqueue_scripts
	 */
	public function enqueue(): void {
		if ( get_template() !== 'enfold' ) {
			return;
		}
		// No file: the handle only carries the inline rule, so caching and minify plugins treat it like any other style.
		wp_register_style(self::HANDLE, false, array(), ASL_CURR_VER_STRING);
		wp_enqueue_style(self::HANDLE);
		wp_add_inline_style(self::HANDLE, self::CSS);
	}
}
