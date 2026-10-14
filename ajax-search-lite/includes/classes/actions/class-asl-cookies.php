<?php
if ( !defined('ABSPATH') ) {
	die('-1');
}

if ( !class_exists('WD_ASL_Cookies_Action') ) {
	/**
	 * Class WD_ASL_Cookies_Action
	 *
	 * Cookie related stuff
	 *
	 * @class         WD_ASL_Cookies_Action
	 * @version       1.0
	 * @package       AjaxSearchLite/Classes/Actions
	 * @category      Class
	 * @author        Ernest Marcinko
	 */
	class WD_ASL_Cookies_Action extends WD_ASL_Action_Abstract {

		public function handle() {
			// Forcefully unset the cookies, if requested
			if ( isset($_GET['asl_unset_cookies']) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$parse = wp_parse_url(get_bloginfo('url'));
				$host  = $parse['host'];
				setcookie('asl_data', '', time() - 7200, '/', $host, is_ssl() );
				setcookie('asl_phrase', '', time() - 7200, '/', $host, is_ssl() );
				unset($_COOKIE['asl_data']);
				unset($_COOKIE['asl_phrase']);
			}

			/**
			 * NOTES
			 * ---------------------------------------
			 * DO NOT DELETE THE COOKIES HERE, UNLESS A CERTAIN CRITERIA MET!!
			 * This filter is executed on ajax requests and redirects and during all
			 * kinds of background tasks. Unset only if the criteria is deterministic,
			 * and applies on a very specific case. (like the example above)
			 */

			// Set cookie if this is search, and asl is active
			// Frontend public request, no need for nonce here
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
			if ( isset($_POST['asl_active'], $_GET['s']) && is_string($_GET['s']) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$_p_data = wp_unslash($_POST['p_asl_data'] ?? $_POST['np_asl_data'] ?? null); // phpcs:ignore
				// A serialized form (sanitize_text_field() would strip its encoded "[]"), validated when used
				if ( is_string($_p_data) ) {
					// Another site can post a search form here, but it can't store the settings for later
					if ( self::isSameSiteRequest() ) {
						$expires = time() + ( 30 * DAY_IN_SECONDS );
						setcookie('asl_data', $_p_data, self::cookieOptions($expires));
						setcookie('asl_phrase', wp_unslash($_GET['s']), self::cookieOptions($expires)); // phpcs:ignore
					}
					$_COOKIE['asl_data']   = wp_slash($_p_data);
					$_COOKIE['asl_phrase'] = $_GET['s']; // phpcs:ignore
				}
			}
		}

		/**
		 * Whether the request was made by the site itself, not by a form or a page on another site
		 * (Fetch Metadata, and the Origin header of a POST). Without either header it can't be told.
		 */
		public static function isSameSiteRequest(): bool {
			$fetch_site = isset($_SERVER['HTTP_SEC_FETCH_SITE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_SEC_FETCH_SITE'])) : '';
			if ( $fetch_site !== '' && !in_array($fetch_site, array( 'same-origin', 'same-site', 'none' ), true) ) {
				return false;
			}
			if ( isset($_SERVER['HTTP_ORIGIN']) ) {
				$origin = wp_parse_url(sanitize_text_field(wp_unslash($_SERVER['HTTP_ORIGIN'])));
				$home   = wp_parse_url(home_url());
				foreach ( array( 'scheme', 'host', 'port' ) as $part ) {
					if ( !is_array($origin) || !is_array($home) || ( $origin[ $part ] ?? '' ) !== ( $home[ $part ] ?? '' ) ) {
						return false;
					}
				}
			}
			return true;
		}

		/**
		 * @param int $expires
		 * @return array{expires: int, path: string, domain: string, secure: bool, httponly: bool, samesite: string}
		 */
		public static function cookieOptions( int $expires ): array {
			$parse = wp_parse_url(get_bloginfo('url'));
			return array(
				'expires'  => $expires,
				'path'     => '/',
				'domain'   => $parse['host'] ?? '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			);
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
