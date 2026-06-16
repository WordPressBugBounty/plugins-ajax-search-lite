<?php
/**
 * WPDreams shared-library bootstrap — version guard (loud-failure layer).
 *
 * Multiple WPDreams plugins each bundle the shared libraries (plugin-core / admin-ui / php-utils)
 * under the same namespaces. PHP loads a class once per request, so when several plugins are active
 * the first-registered autoloader wins and an OLDER copy can shadow a newer consumer, breaking it
 * silently. This file lets each plugin declare the minimum shared-library version it needs; if an
 * older copy is the one actually loaded, an admin notice is shown instead of failing silently.
 *
 * IMPORTANT: this file is `require`d directly (not autoloaded) by each consuming plugin, so the
 * first plugin to include it defines these functions for everyone. Therefore it must stay small and
 * strictly backward compatible — never rename/repurpose an existing function or change its signature.
 *
 * @package WPDRMS\PluginCore
 */

if ( ! function_exists( 'wpdrms_shared_version_class' ) ) {

	/**
	 * Maps a short library key to the FQCN of its Version class.
	 */
	function wpdrms_shared_version_class( string $lib ): string {
		switch ( $lib ) {
			case 'plugin-core':
				return 'WPDRMS\\PluginCore\\Version';
			case 'admin-ui':
				return 'WPDRMS\\AdminUI\\Version';
			case 'php-utils':
			case 'utils':
				return 'WPDRMS\\Utils\\Version';
		}
		return '';
	}

	/**
	 * Returns the version of the actually-loaded copy of $lib, or '' when it can't be determined
	 * (e.g. the loaded copy predates versioning).
	 */
	function wpdrms_shared_loaded_version( string $lib ): string {
		$class = wpdrms_shared_version_class( $lib );
		if ( $class !== '' && class_exists( $class ) && method_exists( $class, 'get' ) ) {
			return (string) call_user_func( array( $class, 'get' ) );
		}
		return '';
	}

	/**
	 * Returns the file path of the actually-loaded copy of $lib (via reflection on its Version class),
	 * or '' when it can't be determined. This is the copy whose version wpdrms_shared_loaded_version()
	 * reports — which is not necessarily the newest registered one, since another plugin's autoloader
	 * may have loaded an older copy first. Used to attribute a conflict to the plugin shipping it.
	 */
	function wpdrms_shared_loaded_source( string $lib ): string {
		$class = wpdrms_shared_version_class( $lib );
		if ( $class === '' || ! class_exists( $class ) ) {
			return '';
		}
		try {
			$file = ( new \ReflectionClass( $class ) )->getFileName();
			return is_string( $file ) ? $file : '';
		} catch ( \ReflectionException $e ) {
			return '';
		}
	}

	/**
	 * Declares that $plugin_label needs $lib >= $min_version. If an older copy is loaded (shadowed by
	 * another active plugin), an admin notice is queued. Returns true when the requirement is met (or
	 * the loaded version can't be determined, in which case we don't cry wolf).
	 */
	function wpdrms_shared_require_version( string $lib, string $min_version, string $plugin_label ): bool {
		$loaded = wpdrms_shared_loaded_version( $lib );
		if ( $loaded === '' || version_compare( $loaded, $min_version, '>=' ) ) {
			return true;
		}

		$GLOBALS['wpdrms_shared_conflicts'][] = array(
			'plugin'     => $plugin_label,
			'lib'        => $lib,
			'min'        => $min_version,
			'loaded'     => $loaded,
			// Path of the actually-loaded (too-old) copy, so the notice can name the plugin that
			// ships it. Taken from the loaded Version class itself, NOT the newest registered copy:
			// another plugin's autoloader may have loaded an older copy first, so the reported
			// `loaded` version and the newest registered copy can differ (which mis-attributed the
			// notice to the consumer plugin instead of the one actually providing the old copy).
			'source_dir' => wpdrms_shared_loaded_source( $lib ),
		);

		static $hooked = false;
		if ( ! $hooked && function_exists( 'add_action' ) ) {
			$hooked = true;
			add_action( 'admin_notices', 'wpdrms_shared_render_conflict_notices' );
		}
		return false;
	}

	/**
	 * Returns the queued conflicts, each enriched with the plugin that ships the loaded copy
	 * ('source_slug' / 'source_name', resolved from 'source_dir'). This is the public accessor
	 * for surfacing the conflicts elsewhere (e.g. the admin-ui React notice component).
	 *
	 * Several active plugins can each declare a requirement on the same shared library, queuing a
	 * separate conflict apiece — so the conflicts are collapsed per library (keeping the strictest
	 * required version) to show one notice per too-old library rather than one per consuming plugin.
	 */
	function wpdrms_shared_get_conflicts(): array {
		$raw = isset( $GLOBALS['wpdrms_shared_conflicts'] ) && is_array( $GLOBALS['wpdrms_shared_conflicts'] )
			? $GLOBALS['wpdrms_shared_conflicts'] : array();

		$by_lib = array();
		foreach ( $raw as $c ) {
			$lib = $c['lib'] ?? '';
			if ( ! isset( $by_lib[ $lib ] ) || version_compare( $c['min'] ?? '0', $by_lib[ $lib ]['min'] ?? '0', '>' ) ) {
				$by_lib[ $lib ] = $c;
			}
		}

		$conflicts = array_values( $by_lib );
		foreach ( $conflicts as &$c ) {
			$source           = wpdrms_shared_plugin_from_dir( $c['source_dir'] ?? '' );
			$c['source_slug'] = $source['slug'];
			$c['source_name'] = $source['name'];
		}
		unset( $c );

		return $conflicts;
	}

	/**
	 * Resolves the plugin that owns a bundled-library directory, given its path
	 * (…/plugins/<slug>/vendor/wpdreams/<lib>/src). Returns a ['slug' => …, 'name' => …] pair —
	 * the folder slug and, when the plugin API is available (admin), the human-readable plugin
	 * name; both '' when the path isn't under a plugin folder.
	 */
	function wpdrms_shared_plugin_from_dir( string $dir ): array {
		$result = array( 'slug' => '', 'name' => '' );
		if ( $dir === '' ) {
			return $result;
		}

		$normalised = str_replace( '\\', '/', $dir );
		if ( ! preg_match( '#/plugins/([^/]+)/#', $normalised, $m ) ) {
			return $result;
		}
		$result['slug'] = $m[1];
		$result['name'] = $m[1];

		if ( ! function_exists( 'get_plugins' ) && defined( 'ABSPATH' ) && is_file( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( function_exists( 'get_plugins' ) ) {
			foreach ( get_plugins() as $file => $data ) {
				if ( strpos( $file, $m[1] . '/' ) === 0 && ! empty( $data['Name'] ) ) {
					$result['name'] = $data['Name'];
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Renders the queued shared-library version-conflict notices.
	 */
	function wpdrms_shared_render_conflict_notices(): void {
		foreach ( wpdrms_shared_get_conflicts() as $c ) {
			if ( $c['source_name'] !== '' ) {
				$message = sprintf(
					/* translators: 1: consumer plugin, 2: library name, 3: required version, 4: loaded version, 5: provider plugin */
					'%1$s needs the shared "%2$s" library version %3$s or newer, but version %4$s is loaded, provided by the "%5$s" plugin. Update "%5$s" to its latest version so the newest shared library loads.',
					$c['plugin'],
					$c['lib'],
					$c['min'],
					$c['loaded'],
					$c['source_name']
				);
			} else {
				$message = sprintf(
					/* translators: 1: consumer plugin, 2: library name, 3: required version, 4: loaded version */
					'%1$s needs the shared "%2$s" library version %3$s or newer, but version %4$s is loaded (bundled by another active WPDreams plugin). Update that plugin to its latest version so the newest shared library loads.',
					$c['plugin'],
					$c['lib'],
					$c['min'],
					$c['loaded']
				);
			}
			printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
		}
	}

	// ---------------------------------------------------------------------------------------------
	// Newest-wins loader. Each plugin registers the shared-library copies it bundles; a prepended
	// autoloader then resolves every shared class from the HIGHEST registered version, so the newest
	// copy serves all active plugins (it must stay backward compatible). It is fail-safe: if it can't
	// resolve a class it simply returns and Composer's per-plugin autoloader handles it as before —
	// so this can only improve resolution, never break what already worked.
	// ---------------------------------------------------------------------------------------------

	/**
	 * Maps each shared package's vendor directory name to its PSR-4 namespace prefix.
	 *
	 * @return array<string, string>
	 */
	function wpdrms_shared_namespaces(): array {
		return array(
			'plugin-core' => 'WPDRMS\\PluginCore\\',
			'admin-ui'    => 'WPDRMS\\AdminUI\\',
			'php-utils'   => 'WPDRMS\\Utils\\',
		);
	}

	/**
	 * Registers one bundled copy of a shared library as a candidate for the newest-wins loader.
	 */
	function wpdrms_shared_register( string $prefix, string $src_dir, string $version ): void {
		$prefix = trim( $prefix, '\\' ) . '\\';
		$GLOBALS['wpdrms_shared_registry'][ $prefix ][] = array(
			'dir'     => rtrim( $src_dir, '/\\' ),
			'version' => (string) $version,
		);
	}

	/**
	 * Convenience: registers every shared package found under a plugin's vendor/wpdreams directory,
	 * reading each copy's version from its version.php. Call this once per plugin, right after
	 * requiring this bootstrap and before Composer's autoloader.
	 */
	function wpdrms_shared_register_dir( string $vendor_wpdreams ): void {
		$vendor_wpdreams = rtrim( $vendor_wpdreams, '/\\' );
		foreach ( wpdrms_shared_namespaces() as $dir => $prefix ) {
			$version_file = $vendor_wpdreams . '/' . $dir . '/version.php';
			$src          = $vendor_wpdreams . '/' . $dir . '/src';
			if ( is_file( $version_file ) && is_dir( $src ) ) {
				wpdrms_shared_register( $prefix, $src, (string) require $version_file );
			}
		}
	}

	/**
	 * Returns the src directory of the highest-version registered copy for a namespace prefix, or ''.
	 */
	function wpdrms_shared_winner_dir( string $prefix ): string {
		$best = null;
		foreach ( $GLOBALS['wpdrms_shared_registry'][ $prefix ] ?? array() as $candidate ) {
			if ( $best === null || version_compare( $candidate['version'], $best['version'], '>' ) ) {
				$best = $candidate;
			}
		}
		return $best['dir'] ?? '';
	}

	/**
	 * PSR-4 autoloader (prepended) that resolves a registered shared namespace from its newest copy.
	 * Returns silently when it can't, so Composer's autoloader remains the fallback.
	 */
	function wpdrms_shared_autoload( string $class ): void {
		foreach ( $GLOBALS['wpdrms_shared_registry'] ?? array() as $prefix => $candidates ) {
			if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
				continue;
			}
			$dir = wpdrms_shared_winner_dir( $prefix );
			if ( $dir !== '' ) {
				$file = $dir . '/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
				if ( is_file( $file ) ) {
					require $file;
				}
			}
			return; // The class belongs to a shared prefix; don't test the others.
		}
	}

	// Register the newest-wins autoloader once, prepended so it wins over Composer's per-plugin loaders.
	spl_autoload_register( 'wpdrms_shared_autoload', true, true );
}
