<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace AjaxSearchLite\Vendor\WPDRMS\Utils;

if ( !defined('ABSPATH') ) {
	die('-1');
}

class Taxonomy {
	/**
	 * Gets a list of taxonomy terms, separated by a comma (or as defined)
	 *
	 * @param string $taxonomy
	 * @param int    $count
	 * @param string $separator
	 * @param array  $args arguments passed to get_terms() or wp_get_post_terms() functions
	 * @return string
	 */
	public static function getTermsList( string $taxonomy, int $count = 5, string $separator = ', ', array $args = array() ): string {
		// Additional keys
		$args  = array_merge(
			$args,
			array(
				'taxonomy' => $taxonomy,
				'fields'   => 'names',
				'number'   => $count,
			)
		);
		$terms = get_terms($args);
		if ( !is_array($terms) ) {
			return '';
		}
		// 'fields' => 'names' asks for strings, but a get_terms filter can return WP_Term objects.
		$names = array();
		foreach ( $terms as $term ) {
			if ( is_string($term) ) {
				$names[] = $term;
			} elseif ( $term instanceof \WP_Term ) {
				$names[] = $term->name;
			}
		}
		return implode($separator, $names);
	}
}
