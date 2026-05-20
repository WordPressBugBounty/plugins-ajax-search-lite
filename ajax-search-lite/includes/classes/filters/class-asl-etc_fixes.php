<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}

if ( !class_exists('WD_ASL_EtcFixes_Filter') ) {
	/**
	 * Class WD_ASL_EtcFixes_Filter
	 *
	 * Other 3rd party plugin related filters
	 *
	 * @class         WD_ASL_EtcFixes_Filter
	 * @version       1.0
	 * @package       AjaxSearchLite/Classes/Filters
	 * @category      Class
	 * @author        Ernest Marcinko
	 */
	class WD_ASL_EtcFixes_Filter extends WD_ASL_Filter_Abstract {
		/**
		 * Executes search shortcodes when placed as menu titles
		 *
		 * @param mixed $menu_items
		 * @return mixed
		 */
		public function allowShortcodeInMenus( $menu_items ) {
			foreach ( $menu_items as $menu_item ) {
				if (
					strpos($menu_item->title, '[wd_asl') !== false ||
					strpos($menu_item->title, '[wpdreams_') !== false
				) {
					$menu_item->title = do_shortcode($menu_item->title);
					$menu_item->url   = '';
				}
			}
			return $menu_items;
		}

		public function switchToNewScriptsOnLiveLoader( $options ) {
			return $options;
		}

		/**
		 * Fix for the Oxygen builder plugin editor error console
		 *
		 * @param mixed $quit
		 */
		public function fixOxygenEditorJS( $quit ) {
			if ( isset($_GET['ct_builder']) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return true;
			}

			return false;
		}

		// ------------------------------------------------------------
		// ---------------- SINGLETON SPECIFIC --------------------
		// ------------------------------------------------------------
		/**
		 * Static instance storage
		 *
		 * @var self
		 */
		protected static $_instance;

		public static function getInstance() {
			if ( ! ( self::$_instance instanceof self ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}
	}
}
