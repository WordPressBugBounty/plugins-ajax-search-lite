<?php

namespace WPDRMS\AdminUI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Surfaces the shared-library version-conflict notices (queued by plugin-core's bootstrap) on the
 * modern admin-ui pages, which suppress WordPress' native admin_notices by design.
 *
 * The conflicts are computed during admin_init by each consuming plugin's
 * wpdrms_shared_require_version() call and read here via wpdrms_shared_get_conflicts(). This class
 * builds the localized messages and exposes them to the React layer as `window.wpdrmsSharedConflicts`
 * (printed once in the admin footer), where {@see SharedLibraryNotices component} renders them.
 */
class SharedLibraryNotices {

	/**
	 * Builds the localized notice messages for the queued shared-library conflicts.
	 *
	 * @return array<int, array{message: string}>
	 */
	public static function data(): array {
		if ( ! function_exists( 'wpdrms_shared_get_conflicts' ) ) {
			return array();
		}

		$messages = array();
		foreach ( wpdrms_shared_get_conflicts() as $conflict ) {
			$messages[] = array( 'message' => self::message( $conflict ) );
		}

		return $messages;
	}

	/**
	 * Builds a single localized conflict message, naming the provider plugin when known.
	 *
	 * @param array<string, string> $conflict
	 */
	private static function message( array $conflict ): string {
		$plugin = $conflict['plugin'] ?? '';
		$lib    = $conflict['lib'] ?? '';
		$min    = $conflict['min'] ?? '';
		$loaded = $conflict['loaded'] ?? '';

		if ( ! empty( $conflict['source_name'] ) ) {
			return sprintf(
				/* translators: 1: consumer plugin, 2: library name, 3: required version, 4: loaded version, 5: provider plugin */
				__( '%1$s needs the shared "%2$s" library version %3$s or newer, but version %4$s is loaded, provided by the "%5$s" plugin. Update "%5$s" to its latest version so the newest shared library loads.', 'wpdrms-admin-ui' ),
				$plugin,
				$lib,
				$min,
				$loaded,
				$conflict['source_name']
			);
		}

		return sprintf(
			/* translators: 1: consumer plugin, 2: library name, 3: required version, 4: loaded version */
			__( '%1$s needs the shared "%2$s" library version %3$s or newer, but version %4$s is loaded (bundled by another active WPDreams plugin). Update that plugin to its latest version so the newest shared library loads.', 'wpdrms-admin-ui' ),
			$plugin,
			$lib,
			$min,
			$loaded
		);
	}

	/**
	 * Registers the footer printer that exposes the conflicts to the React layer. Idempotent, so it
	 * is safe for several active WPDreams plugins to call it (the class loads once, newest-wins).
	 */
	public static function register(): void {
		static $registered = false;
		if ( $registered ) {
			return;
		}
		$registered = true;
		add_action( 'admin_print_footer_scripts', array( __CLASS__, 'printData' ), 9 );
	}

	/**
	 * Prints `window.wpdrmsSharedConflicts` in the admin footer when there are conflicts to report.
	 */
	public static function printData(): void {
		$data = self::data();
		if ( empty( $data ) ) {
			return;
		}

		printf(
			'<script>window.wpdrmsSharedConflicts = %s;</script>',
			wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded with tag/amp hex escaping for safe inline-script embedding.
		);
	}
}
