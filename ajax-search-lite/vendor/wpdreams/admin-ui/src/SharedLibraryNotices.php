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
				// Plain string, no __(): a shared library cannot know its consumer's text domain, and any
				// literal domain here mismatches the plugin slug (Plugin Check TextDomainMismatch, an ERROR).
				// No translations were ever shipped for the library domain, so nothing is lost.
				'%1$s needs the shared "%2$s" library version %3$s or newer, but version %4$s is loaded, provided by the "%5$s" plugin. Update "%5$s" to its latest version so the newest shared library loads.',
				$plugin,
				$lib,
				$min,
				$loaded,
				$conflict['source_name']
			);
		}

		return sprintf(
			// See above: shared-library strings carry no text domain.
			'%1$s needs the shared "%2$s" library version %3$s or newer, but version %4$s is loaded (bundled by another active WPDreams plugin). Update that plugin to its latest version so the newest shared library loads.',
			$plugin,
			$lib,
			$min,
			$loaded
		);
	}

	/**
	 * Registers the footer printer that exposes the conflicts to the React layer.
	 *
	 * Uses a fixed $GLOBALS key as the dedup guard rather than a class-static, so the
	 * registration fires exactly once per site even when each active WPDreams plugin runs
	 * its own build-time-scoped copy of this class (scoping gives each plugin an independent
	 * class identity, so a class-static would fire once per plugin instead of once per site).
	 */
	public static function register(): void {
		if ( ! empty( $GLOBALS['wpdrms_admin_ui_shared_notices_registered'] ) ) {
			return;
		}
		$GLOBALS['wpdrms_admin_ui_shared_notices_registered'] = true;
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
