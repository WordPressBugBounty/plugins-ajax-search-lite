<?php

namespace AjaxSearchLite\Vendor\WPDRMS\Utils;

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

/**
 * Normalises values handed over by code we do not control (hook arguments, filter results)
 * before they reach typed code. A hook callback that declares `int $post_id` turns a foreign
 * caller's string into a fatal TypeError; these helpers decide instead, identically on every
 * PHP version (PHP 7.4 coerces '12abc' to 12, PHP 8 refuses it).
 */
class Arg {
	/**
	 * A positive integer id, or null for anything else.
	 *
	 * Accepts an int above zero and a string of decimal digits without padding that fits an int
	 * (core passes meta ids as database strings). Rejects everything else: 0 and negatives,
	 * '05', ' 5', '1e3', '12abc', a meta box id like 'aec_common_settings', floats, booleans,
	 * arrays and objects — a WP_Post included, the caller decides whether an object means its id.
	 *
	 * @param mixed $value
	 * @return positive-int|null
	 */
	public static function positiveInt( $value ): ?int {
		if ( is_int($value) ) {
			return $value > 0 ? $value : null;
		}
		if ( is_string($value) && (string) (int) $value === $value ) {
			$id = (int) $value;
			return $id > 0 ? $id : null;
		}
		return null;
	}
}
