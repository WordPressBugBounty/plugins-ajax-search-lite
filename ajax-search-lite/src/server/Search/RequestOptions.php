<?php

namespace WPDRMS\ASL\Search;

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

/**
 * The options a search request posts (the search form, options[...]) that the search reads.
 *
 * ASL_Helpers::toQueryArgs() reads these and nothing else, and the results cache keys a response on
 * these and nothing else (ResultsCacheService::cacheKey()): whatever else a request carries cannot
 * change the response, so it must not change the key either, or junk keys would fill the cache with
 * copies of the same response. The super file cache front-end computes the same key, from the same
 * list (printed as ASL.cache.option_keys).
 */
class RequestOptions {
	const KEYS = array(
		'asl_gen',
		'customset',
		'categoryset',
		'post_parent',
		'qtranslate_lang',
		'wpml_lang',
		'polylang_lang',
		'current_page_id',
		'filters_changed',
		'filters_initial',
		'woo_currency',
	);

	/**
	 * The read options only, in a fixed order, lists as lists of strings and anything else as a string:
	 * the same request data always gives the same key, in PHP and in the front-end.
	 *
	 * @param array<string, mixed> $options
	 * @return array<string, string|string[]>
	 */
	public static function normalize( array $options ): array {
		$normalized = array();
		foreach ( self::KEYS as $key ) {
			if ( !isset($options[ $key ]) ) {
				continue;
			}
			$value = $options[ $key ];
			if ( is_array($value) ) {
				$normalized[ $key ] = array_values(array_map('strval', array_filter($value, 'is_scalar')));
			} elseif ( is_scalar($value) ) {
				$normalized[ $key ] = (string) $value;
			}
		}
		return $normalized;
	}
}
