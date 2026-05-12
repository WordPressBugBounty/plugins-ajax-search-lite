<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace WPDRMS\Utils;

class PostMeta {
	/**
	 * Gets the Maximum value of a post metadata field
	 *
	 * @param string $field
	 * @return int
	 */
	public static function getNumericFieldMax( string $field ): int {
		global $wpdb;

		$max = wp_cache_get('wpd_f_max_field_' . $field, 'wpd');
		if ( $max === false ) {
			// WHERE meta_key = %s is ~10x faster as WHERE meta_key LIKE %s
			$max = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX(CAST(meta_value as SIGNED)) FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value <> ''",
					$field
				)
			);
			if ( !is_wp_error($max) && is_numeric($max) ) {
				wp_cache_set( 'wpd_f_max_field_' . $field, $max, 'wpd' );
			}
		}

		return intval($max);
	}

	/**
	 * Gets the minimum value of a post metadata field
	 *
	 * @param string $field
	 * @return int
	 */
	public static function getNumericFieldMin( string $field ): int {
		global $wpdb;

		$min = wp_cache_get('wpd_f_min_field_' . $field, 'wpd');
		if ( $min === false ) {
			// WHERE meta_key = %s is ~10x faster as WHERE meta_key LIKE %s
			$min = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MIN(CAST(meta_value as SIGNED)) FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value <> ''",
					$field
				)
			);
			if ( !is_wp_error($min) && is_numeric($min) ) {
				wp_cache_set( 'wpd_f_min_field_' . $field, $min, 'wpd' );
			}
		}

		return intval($min);
	}
}
