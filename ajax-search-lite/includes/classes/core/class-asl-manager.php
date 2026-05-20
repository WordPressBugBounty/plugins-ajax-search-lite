<?php

use WPDRMS\ASL\Cache\ResultsCacheService;
use WPDRMS\ASL\Core\Factory;
use WPDRMS\ASL\Statistics\StatisticsService;
use WPDRMS\ASL\Utils\Polylang\StringTranslations;
use WPDRMS\Utils\FileManager;

if ( !defined('ABSPATH') ) {
	die('-1');
}

if ( !class_exists('WD_ASL_Manager') ) {
	/**
	 * Class WD_ASL_Manager
	 *
	 * This is the main controller class of the plugin, should be instantiated from the plugin main file.
	 *
	 * @class         WD_ASL_Manager
	 * @version       1.0
	 * @package       AjaxSearchLite/Classes/Core
	 * @category      Class
	 * @author        Ernest Marcinko
	 */
	class WD_ASL_Manager {

		/**
		 * Context of the current WP environment
		 *
		 * Is used to include the correct and only necessary files for each context to save performance
		 *
		 * Possible values:
		 *  ajax - an ajax call triggered by the search
		 *  frontend - simple front-end call, or an ajax request not triggered by ASP
		 *  backend - on any of the plugin back-end pages
		 *  global_backend - on any other back-end page
		 *  special - special cases
		 *
		 * @since 1.0
		 * @var string
		 */
		private $context = 'frontend';

		/**
		 * Core singleton class
		 *
		 * @var WD_ASL_Manager self
		 */
		private static $_instance;

		/**
		 * Initialize and run the plugin-in
		 */
		private function __construct() {

			do_action('wd_asl_before_load');

			$this->preLoad();
			$this->loadInstances();
			$this->initUploadGlobals();
			$this->initCacheGlobals();

			FileManager::instance()->setAllowedDirectories(
				array(
					wd_asl()->upload_path,
					wd_asl()->bfi_path,
					wd_asl()->cache_path,
				)
			);

			/**
			 * Available after this point:
			 *      (WD_ASL_Init) wd_asl()->instances, (global) $wd_asl->instances
			 */
			register_activation_hook(ASL_FILE, array( $this, 'activationHook' ));
			/**
			 * Available after this point:
			 *      (array) wd_asl()->options, (global) $wd_asl->options
			 *      (WD_ASL_Init) wd_asl()->init, (global) $wd_asl->init
			 *      (WD_ASL_DBMan) wd_asl()->db, (global) $wd_asl->db
			 */
			add_action( 'init', array( $this, 'init' ), 0 );
		}

		public function init() {
			// Check if the plugin needs to be stopped on certain conditions
			// ..this needs to be here, otherwise filter not accesible from functions.php
			if ( $this->stopLoading() ) {
				return false;
			}

			$this->getContext();
			/**
			 * Available after this point:
			 *      $this->context
			 */
			$this->loadIncludes();
			$this->loadShortcodes();
			$this->loadAssets();
			$this->loadMenu();

			$this->loadHooks();

			wd_asl()->init->safety_check();

			// Late init, just before footer print scripts
			add_action('wp_footer', array( $this, 'lateInit' ), 99);

			do_action('wd_asl_loaded');

			return true;
		}

		private function stopLoading() {
			$ret = false;

			if ( isset($_GET, $_GET['action']) ) { // phpcs:ignore
				if ( $_GET['action'] === 'ere_property_search_ajax' ) { // phpcs:ignore
					$ret = true;
				}
			}

			// Allow filtering this condition
			return apply_filters('asl_stop_loading', $ret);
		}


		/**
		 *  Preloading: for functions and other stuff needed
		 */
		private function preLoad() {
			require_once ASL_PATH . '/backend/settings/default_options.php';
			require_once ASL_CLASSES_PATH . 'etc/class.asl-mb.php';

			// We need to initialize the init here to get the init->table() function
			wd_asl()->init = WD_ASL_Init::getInstance();

			StringTranslations::init();
		}

		/**
		 * Gets the upload path with back-slash
		 */
		public function initUploadGlobals() {
			if ( is_multisite() ) {
				/**
				 * On multisite, the wp_upload_dir() returns the current blog URLs,
				 * even if the switch_to_blog(1) is initiated (WordPress core bug?)
				 *
				 * Bypass solution: Save the upload dir option as a site option, when the main blog is visited,
				 *                  then get this value on each site, with a fallback.
				 * WARNING: DO NOT USE get_site_option() and update_site_option() - as those slow down the multisite loading by a LOT
				 *          use get_blog_option() and update_blog_option() instead
				 */
				$upload_dir = wp_upload_dir();
				if ( get_current_blog_id() == get_network()->site_id ) {
					update_blog_option(get_network()->site_id, '_asp_upload_dir', $upload_dir);
				} else {
					$upload_dir = get_blog_option(get_network()->site_id, '_asp_upload_dir', $upload_dir);
				}
			} else {
				$upload_dir = wp_upload_dir();
			}

			$upload_dir = apply_filters('asp_glob_upload_dir', $upload_dir);

			wd_asl()->upload_path = $upload_dir['basedir'] . '/' . wd_asl()->upload_dir . '/';
			wd_asl()->upload_url  = $upload_dir['baseurl'] . '/' . wd_asl()->upload_dir . '/';
			// Let us make sure, that the URL is using the correct protocol
			if ( defined('ASP_URL') ) {
				// Site is https, but URL is http
				if ( strpos(ASP_URL, 'https://') === 0 && strpos(wd_asl()->upload_url, 'https://') === false ) {
					wd_asl()->upload_url = str_replace('http://', 'https://', wd_asl()->upload_url);
					// Site is http, but URL is https
				} elseif ( strpos(ASP_URL, 'http://') === 0 && strpos(wd_asl()->upload_url, 'http://') === false ) {
					wd_asl()->upload_url = str_replace('https://', 'http://', wd_asl()->upload_url);
				}
			}

			if ( defined( 'BFITHUMB_UPLOAD_DIR' ) ) {
				wd_asl()->bfi_path = $upload_dir['basedir'] . '/' . BFITHUMB_UPLOAD_DIR . '/';
			} else {
				wd_asl()->bfi_path = $upload_dir['basedir'] . '/' . wd_asl()->bfi_dir . '/';
			}

			// Allow globals modification for developers
			wd_asl()->upload_path = apply_filters('asp_glob_upload_path', wd_asl()->upload_path);
			wd_asl()->upload_url  = apply_filters('asp_glob_upload_url', wd_asl()->upload_url);
			wd_asl()->bfi_path    = apply_filters('asp_glob_bfi_path', wd_asl()->bfi_path);
		}


		public function initCacheGlobals() {
			$base_url = WP_CONTENT_URL;
			if ( defined('ASP_URL') ) {
				if ( strpos(ASP_URL, 'https://') === 0 && strpos($base_url, 'https://') === false ) {
					$base_url = str_replace('http://', 'https://', $base_url);
				}
			}
			wd_asl()->global_cache_path = apply_filters('asp_glob_global_cache_path', WP_CONTENT_DIR . '/cache/');
			wd_asl()->cache_path        = apply_filters('asp_glob_cache_path', WP_CONTENT_DIR . '/cache/asp/');
			wd_asl()->cache_url         = apply_filters('asp_glob_cache_url', $base_url . '/cache/asp/');
		}
		
		/**
		 * Gets the call context for further use
		 */
		/**
		 * Returns true for backend pages that are pure React UI and don't need
		 * the legacy option types (jQuery UI, wpdreams-* scripts/styles) or
		 * the frontend search instance scripts.
		 */
		private function isReactPage(): bool {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
			return in_array($page, array( 'asl_cache', 'asl_statistics', 'asl_analytics', 'asl_compatibility', 'asl_maintenance', 'asl_help_and_support' ), true);
		}

		public function getContext() {

			$backend_pages = WD_ASL_Menu::getMenuPages();

			/**
			 * Known actions, only checking
			 */
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( !empty($_POST['action']) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( in_array($_POST['action'], WD_ASL_Ajax::getAll(), true) ) {
					$this->context = 'ajax';
				}
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( isset($_POST['wd_required']) ) {
					$this->context = 'special';
				}
				// If it is not part of the plugin ajax actions, the context stays "frontend"
			} elseif ( !empty($_GET['page']) && in_array($_GET['page'], $backend_pages, true) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$this->context = 'backend';
			} elseif ( is_admin() ) {
				$this->context = 'global_backend';
			} else {
				$this->context = 'frontend';
			}

			return $this->context;
		}

		/**
		 * Loads the instance data into the global scope
		 */
		private function loadInstances() {

			wd_asl()->instances = WD_ASL_Instances::getInstance();
		}

		/**
		 * Loads the required files based on the context
		 */
		private function loadIncludes() {

			require_once ASL_FUNCTIONS_PATH . 'functions.php';
			require_once ASL_CLASSES_PATH . 'ajax/ajax.inc.php';
			require_once ASL_CLASSES_PATH . 'filters/filters.inc.php';
			require_once ASL_CLASSES_PATH . 'etc/class-asl_helpers.php';
			require_once ASL_CLASSES_PATH . 'shortcodes/shortcodes.inc.php';

			switch ( $this->context ) {
				case 'special':
					require_once ASL_PATH . '/backend/settings/types.inc.php';
					break;
				case 'ajax':
					break;
				case 'frontend':
					break;
				case 'backend':
					if ( !$this->isReactPage() ) {
						require_once ASL_PATH . '/backend/settings/types.inc.php';
					}
					break;
				case 'global_backend':
					break;
				default:
					break;
			}

			// Lifting some weight off from ajax requests
			if ( $this->context !== 'ajax' ) {
				require_once ASL_CLASSES_PATH . 'actions/actions.inc.php';
				/* Includes on Post/Page/Custom post type edit pages */
				require_once ASL_CLASSES_PATH . 'widgets/widgets.inc.php';
			}
		}

		/**
		 * Use the Shorcodes loader to assign the shortcodes to handler classes
		 */
		private function loadShortcodes() {

			WD_ASL_Shortcodes::registerAll();
		}

		/**
		 * Runs the Assets loader
		 */
		private function loadAssets() {
			// JS
			// WD_MS_Assets::loadJS("ms_search_js");

			// CSS
			// WD_MS_Assets::loadCSS("ms_search_css_basic");

			if ( $this->context === 'backend' && !$this->isReactPage() ) {
				add_action('admin_enqueue_scripts', array( wd_asl()->init, 'scripts' ));
			}

			if ( $this->context === 'frontend' || $this->context === 'backend' ) {
				add_action('wp_enqueue_scripts', array( wd_asl()->init, 'styles' ));
				add_action('wp_enqueue_scripts', array( wd_asl()->init, 'scripts' ));
				add_action('wp_footer', array( wd_asl()->init, 'footer' ));
			}
		}

		/**
		 * Generates the menu
		 */
		private function loadMenu() {

			add_action('admin_menu', array( 'WD_ASL_Menu', 'register' ));
		}

		private function loadHooks() {
			$factory = Factory::instance();
			// Register handlers only if the context is ajax indeed
			if ( $this->context === 'ajax' ) {
				WD_ASL_Ajax::registerAll();
			}

			if ( $this->context !== 'ajax' ) {
				// Editor blocks
				foreach ( $factory->get( 'Block' ) as $block ) {
					$block->register();
				}

				if ( $this->context === 'backend' ) {
					WD_ASL_Actions::register('admin_init', 'Compatibility');
				}

				WD_ASL_Actions::registerAll();

				add_action(
					'rest_api_init',
					function () {
						foreach ( Factory::instance()->get('Rest') as $rest ) {
							$rest->registerRoutes( ASL_DIR );
						}
					}
				);
			}

			StatisticsService::instance()->loadHooks();
			ResultsCacheService::instance()->loadHooks();
			WD_ASL_Filters::registerAll();
		}

		/**
		 * Run at the plugin activation
		 */
		public function activationHook() {

			// Run the activation tasks
			wd_asl()->init->activate();
		}

		/**
		 * This is triggered in the footer. Used for conditional loading assets and stuff.
		 */
		public function lateInit() {}


		// ------------------------------------------------------------
		// ---------------- SINGLETON SPECIFIC --------------------
		// ------------------------------------------------------------

		/**
		 * Get the instane of WD_ASL_Manager
		 *
		 * @return self
		 */
		public static function getInstance() {
			if ( !( self::$_instance instanceof self ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}
	}
}
