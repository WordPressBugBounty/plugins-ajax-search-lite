<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}

if ( !class_exists('WD_ASL_Menu') ) {
	/**
	 * Class WD_ASL_Menu
	 *
	 * Menu handler for Ajax Search Pro plugin. This class encapsulates the menu elements as well.
	 *
	 * @class         WD_ASL_Menu
	 * @version       1.0
	 * @package       AjaxSearchLite/Classes/Core
	 * @category      Class
	 * @author        Ernest Marcinko
	 */
	class WD_ASL_Menu {

		/**
		 * Holds the main menu item
		 *
		 * @var array the main menu
		 */
		private static $main_menu = array(
			'title'    => 'Ajax Search Lite',
			'slug'     => 'asl_settings',
			'file'     => '/backend/settings.php',
			'position' => '59.436',
			'icon_url' => 'icon.png',
		);

		private static $hooks = array();

		/**
		 * Submenu titles and slugs
		 *
		 * @var array
		 */
		private static $submenu_items = array();

		/**
		 * Bypass method to support translations, because static array varialbes cannot have a value defined as a result
		 * of a function, like 'key' => __('text', ..)
		 */
		private static function preInit() {
			if ( count(self::$submenu_items) === 0 ) {
				$main_menu     = array(
					'title'    => __('Ajax Search Lite', 'ajax-search-lite'),
					'slug'     => 'asl_settings',
					'file'     => '/backend/settings.php',
					'position' => '59.436',
					'icon_url' => 'img/svg/asl-menu-icon.svg',
				);
				$submenu_items = array(
					array(
						'title' => __('Search Statistics', 'ajax-search-lite'),
						'file'  => '/backend/statistics.php',
						'slug'  => 'asl_statistics',
					),
					array(
						'title' => __('Analytics Integration', 'ajax-search-lite'),
						'file'  => '/backend/analytics.php',
						'slug'  => 'asl_analytics',
					),
					array(
						'title' => __('Cache', 'ajax-search-pro'),
						'file'  => '/backend/cache.php',
						'slug'  => 'asl_cache',
					),
					array(
						'title' => __('Compatibility Settings', 'ajax-search-lite'),
						'file'  => '/backend/compatibility.php',
						'slug'  => 'asl_compatibility',
					),
					array(
						'title' => __('Maintenance', 'ajax-search-lite'),
						'file'  => '/backend/maintenance.php',
						'slug'  => 'asl_maintenance',
					),
					array(
						'title' => "<span class='asl_menu_help'>Help & Support</span>",
						'file'  => '/backend/help_and_support.php',
						'slug'  => 'asl_help_and_support',
					),
					array(
						'title' => "<span class='asl_menu_pro'>Go PRO</span>",
						'file'  => '/backend/go_pro.php',
						'slug'  => 'asl_go_pro',
					),
				);

				self::$main_menu     = $main_menu;
				self::$submenu_items = $submenu_items;
			}
		}

		/**
		 * Resolves a menu icon path to a value usable by add_menu_page().
		 *
		 * SVG files are inlined as a base64 data URI so the WordPress sidebar renders a
		 * crisp vector that wp-admin/js/svg-painter.js can recolour for the admin colour
		 * scheme. Anything else — or an unreadable SVG — falls back to a plain plugin URL.
		 *
		 * @param string $rel Plugin-relative icon path (e.g. 'img/svg/asl-menu-icon.svg').
		 * @return string A data URI or a plugin URL.
		 */
		public static function menuIcon( $rel ) {
			if ( substr( $rel, -4 ) === '.svg' ) {
				$file = ASL_PATH . ltrim( $rel, '/' );
				if ( is_readable( $file ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a small SVG bundled with the plugin; wp_remote_get() is for remote URLs.
					$svg = file_get_contents( $file );
					if ( $svg !== false ) {
						// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Building a standard data:image/svg+xml;base64 URI for the admin menu icon.
						return 'data:image/svg+xml;base64,' . base64_encode( $svg );
					}
				}
			}

			return ASL_URL . $rel;
		}

		/**
		 * Runs the menu registration process
		 */
		public static function register() {

			$capability = 'manage_options';

			self::preInit();

			add_action('admin_head', array('WD_ASL_Menu', 'upgradeMenuStyle'));
			add_action('admin_init', array('WD_ASL_Menu', 'maybeRedirectUpgrade'));

			$h                 = add_menu_page(
				self::$main_menu['title'],
				self::$main_menu['title'],
				$capability,
				self::$main_menu['slug'],
				array( 'WD_ASL_Menu', 'route' ),
				self::menuIcon( self::$main_menu['icon_url'] ),
				self::$main_menu['position']
			);
			self::$hooks[ $h ] = self::$main_menu['slug'];

			foreach ( self::$submenu_items as $submenu ) {
				$h                 = add_submenu_page(
					self::$main_menu['slug'],
					self::$main_menu['title'],
					$submenu['title'],
					$capability,
					$submenu['slug'],
					array( 'WD_ASL_Menu', 'route' )
				);
				self::$hooks[ $h ] = $submenu['slug'];
			}
		}

		/** URL for the upgrade/pricing page including UTM parameters */
		const UPGRADE_URL = 'https://ajaxsearchpro.com/pricing?utm_source=ajax-search-lite&utm_medium=admin-menu&utm_campaign=upgrade';

		public static function maybeRedirectUpgrade() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
			if ( $page === 'asl_go_pro' ) {
				wp_redirect(self::UPGRADE_URL);
				exit;
			}
		}

		public static function upgradeMenuStyle() {
			?>
			<style>
				#adminmenu a[href$="page=asl_go_pro"],
				#adminmenu a[href*="ajaxsearchpro.com"],
				#adminmenu a[href$="page=asl_go_pro"]:focus,
				#adminmenu a[href*="ajaxsearchpro.com"]:focus,
				#adminmenu a[href$="page=asl_go_pro"]:hover,
				#adminmenu a[href*="ajaxsearchpro.com"]:hover {
					color: #fff !important;
					background: linear-gradient(135deg, #f97316, #ea580c) !important;
					border-radius: 4px !important;
					margin: 6px 12px 2px !important;
					padding: 6px 10px !important;
					font-weight: 700 !important;
					text-align: center !important;
					display: block !important;
					box-shadow: 0 2px 6px rgba(234,88,12,.4) !important;
				}
			</style>
			<script>
			document.addEventListener('DOMContentLoaded', function () {
				var link = document.querySelector('#adminmenu a[href$="page=asl_go_pro"]');
				if (link) {
					link.setAttribute('target', '_blank');
					link.setAttribute('rel', 'noopener noreferrer');
					link.href = '<?php echo esc_js(self::UPGRADE_URL); ?>';
				}
			});
			</script>
			<?php
		}

		public static function route() {
			$current_view = self::$hooks[ current_filter() ];
			include ASL_PATH . 'backend/' . str_replace('asl_', '', $current_view) . '.php';
		}

		/**
		 * Method to obtain the menu pages for context checking
		 *
		 * @return array
		 */
		public static function getMenuPages() {
			self::preInit();
			$ret = array();

			$ret[] = self::$main_menu['slug'];

			foreach ( self::$submenu_items as $menu ) {
				$ret[] = $menu['slug'];
			}

			return $ret;
		}
	}
}
