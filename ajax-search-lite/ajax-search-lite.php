<?php
/*
Plugin Name: Ajax Search Lite
Plugin URI: http://wp-dreams.com
Description: The lite version of the most powerful ajax powered search engine for WordPress.
Version: 4.14.5
Author: Ernest Marcinko
License: GPLv2
Author URI: http://wp-dreams.com
Text Domain: ajax-search-lite
Domain Path: /languages/
*/

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

define('ASL_PATH', plugin_dir_path(__FILE__));
define('ASL_FILE', __FILE__);
define('ASL_INCLUDES_PATH', plugin_dir_path(__FILE__) . '/includes/');
define('ASL_CLASSES_PATH', plugin_dir_path(__FILE__) . '/includes/classes/');
define('ASL_AUTOLOAD_PATH', plugin_dir_path(__FILE__) . '/src/server/');
define('ASL_FUNCTIONS_PATH', plugin_dir_path(__FILE__) . '/includes/functions/');
define('ASL_DIR', 'ajax-search-lite');
define('ASL_SITE_IS_PROBABLY_SSL', strpos(home_url('/'), 'https://') !== false || strpos(plugin_dir_url(__FILE__), 'https://') !== false);
define(
	'ASL_URL',
	ASL_SITE_IS_PROBABLY_SSL ?
		str_replace('http://', 'https://', plugin_dir_url(__FILE__)) : plugin_dir_url(__FILE__)
);
define('ASL_URL_NP', str_replace(array( 'http://', 'https://' ), '//', plugin_dir_url(__FILE__)));
define('ASL_CURRENT_VERSION', 4787);
define('ASL_CURR_VER_STRING', '4.14.5');
define('ASL_DEBUG', 0);

// The one and most important global
global $wd_asl;

// Shared-library bootstrap: register the shared copies we bundle so the newest version wins across
// active WPDreams plugins (an older copy from another plugin can't shadow ours), and warn loudly in
// the admin if an older shared copy is the one actually loaded. Guarded for copies predating the
// bootstrap. See wpdreams/plugin-core#9.
$asl_shared_bootstrap = ASL_PATH . 'vendor/wpdreams/plugin-core/bootstrap.php';
if ( file_exists( $asl_shared_bootstrap ) ) {
	require_once $asl_shared_bootstrap;
	if ( function_exists( 'wpdrms_shared_register_dir' ) ) {
		wpdrms_shared_register_dir( ASL_PATH . 'vendor/wpdreams' );
	}
	if ( function_exists( 'wpdrms_shared_require_version' ) ) {
		add_action( 'admin_init', static function () {
			wpdrms_shared_require_version( 'admin-ui', '1.3.0', 'Ajax Search Lite' );
			wpdrms_shared_require_version( 'plugin-core', '1.2.0', 'Ajax Search Lite' );
			// 1.0.2 fixes the unauthenticated PHP Object Injection in Str::anyToString() (php-utils#25).
			wpdrms_shared_require_version( 'php-utils', '1.0.2', 'Ajax Search Lite' );

			// Surface shared-library conflicts on the modern admin pages (which hide admin_notices).
			// Guarded: when an older admin-ui copy is loaded the class is absent — the require_version
			// notice above already covers that case, so this must not fatal.
			if ( class_exists( '\WPDRMS\AdminUI\SharedLibraryNotices' ) ) {
				\WPDRMS\AdminUI\SharedLibraryNotices::register();
			}
		} );
	}
}

require_once ASL_PATH . 'vendor/autoload.php';
require_once ASL_AUTOLOAD_PATH . 'Autoloader.php';
require_once ASL_CLASSES_PATH . 'core/core.inc.php';
/**
 * Available:
 *  wd_asl()->_prefix   => correct DB prefix for ASP databases
 *  wd_asl()->tables    => table names
 *  wd_asl()->db        => DB manager
 *  wd_asl()->options   => array of default option arrays
 *  wd_asl()->o         => alias of wd_asl()->options
 *  wd_asl()->instances => array of search instances and data
 *  wd_asl()->init      => initialization object
 *  wd_asl()->manager   => main manager object
 */
$wd_asl = new WD_ASL_Globals();

if ( !function_exists('wd_asl') ) {
	/**
	 * Easy access of the global variable reference
	 *
	 * @return WD_ASL_Globals
	 */
	function wd_asl(): WD_ASL_Globals {
		global $wd_asl;
		return $wd_asl;
	}
}

// Initialize the plugin
$wd_asl->manager = WD_ASL_Manager::getInstance();
