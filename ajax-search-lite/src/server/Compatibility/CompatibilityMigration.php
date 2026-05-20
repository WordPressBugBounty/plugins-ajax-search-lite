<?php

namespace WPDRMS\ASL\Compatibility;

use WPDRMS\ASL\Compatibility\ORM\CompatibilityOptions;

/**
 * One-shot migration from the legacy flat asl_compatibility + asl_performance options
 * to the new asl_compatibility_options ORM. Idempotent — skips if ORM already exists.
 */
class CompatibilityMigration {

	public static function run(): void {
		// Already migrated — skip.
		if ( get_site_option( 'asl_compatibility_options', false ) !== false ) {
			return;
		}

		$legacy = get_option( 'asl_compatibility', false );

		// Fresh install — nothing to migrate.
		if ( $legacy === false ) {
			return;
		}

		$perf = get_option( 'asl_performance', array() );

		$args = array(
			'js_source'                      => array( 'value' => $legacy['js_source'] ?? 'jqueryless-min' ),
			'script_loading_method'          => array( 'value' => $legacy['script_loading_method'] ?? 'classic' ),
			'db_force_case'                  => array( 'value' => $legacy['db_force_case'] ?? 'none' ),
			'init_instances_inviewport_only' => array( 'value' => (bool) ( $legacy['init_instances_inviewport_only'] ?? true ) ),
			'detect_ajax'                    => array( 'value' => (bool) ( $legacy['detect_ajax'] ?? true ) ),
			'load_google_fonts'              => array( 'value' => (bool) ( $legacy['load_google_fonts'] ?? true ) ),
			'query_soft_check'               => array( 'value' => (bool) ( $legacy['query_soft_check'] ?? false ) ),
			'use_acf_getfield'               => array( 'value' => (bool) ( $legacy['use_acf_getfield'] ?? false ) ),
			'db_force_unicode'               => array( 'value' => (bool) ( $legacy['db_force_unicode'] ?? false ) ),
			'db_force_utf8_like'             => array( 'value' => (bool) ( $legacy['db_force_utf8_like'] ?? false ) ),
			'use_custom_ajax_handler'        => array( 'value' => (bool) ( $perf['use_custom_ajax_handler'] ?? false ) ),
			'load_in_footer'                 => array( 'value' => (bool) ( $perf['load_in_footer'] ?? true ) ),
		);

		try {
			CompatibilityOptions::instance()->setArgs( $args )->save();
		} catch ( \Exception $e ) {
			// Migration failure is non-fatal; defaults will be used.
		}
	}
}
