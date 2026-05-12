<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace WPDRMS\Utils;

if ( !defined('ABSPATH') ) {
	die('-1');
}

/**
 * Class ParseStr
 *
 * Original class ParseStr
 *
 * @author boryo at https://github.com/boryo/php_parse_str
 */
class ParseStr {
	/**
	 * Defines the max recursion depth while parsing the query string parameters
	 */
	const MAX_QUERY_DEPTH = 10;

	/**
	 * Do the same as parse_str without max_input_vars limitation
	 *
	 * @param string $s String to parse
	 * @param mixed  $result Variables are stored in this variable as array elements
	 **/
	public static function parse( string $s, &$result ) {
		$result = array();
		if ( $s === '' ) {
			return;
		}
		$vars = explode('&', $s);
		foreach ( $vars as $var ) {
			$eq_pos = strpos($var, '=');
			if ( false === $eq_pos ) {
				continue;
			}
			$key   = substr($var, 0, $eq_pos);
			$value = urldecode(substr($var, $eq_pos + 1));
			static::setQueryArrayValue($key, $result, $value);
		}
	}

	/**
	 * Sets array value by query string path
	 *
	 * Example: var[key][] is set to $array['var']['key'][]
	 *
	 * @param string   $path The current path that is parsed
	 * @param string[] $arr The array to save da data to
	 * @param string   $value The value to set in the array
	 * @param int      $depth Internal parameter used to measure the depth of the recursion
	 */
	private static function setQueryArrayValue( string $path, array &$arr, string $value, int $depth = 0 ) {
		if ( $depth > static::MAX_QUERY_DEPTH ) {
			return;
		}
		$arr_sign_pos = strpos($path, '[');
		if ( false === $arr_sign_pos ) {
			$arr[ $path ] = $value;
			return;
		}
		$key              = substr($path, 0, $arr_sign_pos);
		$array_e_sign_pos = strpos($path, ']', $arr_sign_pos);
		if ( false === $array_e_sign_pos ) {
			return;
		}
		$subkey = substr($path, $arr_sign_pos + 1, $array_e_sign_pos - $arr_sign_pos - 1);
		if ( empty($arr[ $key ]) || !is_array($arr[ $key ]) ) {
			$arr[ $key ] = array();
		}
		if ( $subkey !== '' ) {
			$right = substr($path, $array_e_sign_pos + 1);
			if ( '[' !== substr($right, 0, 1) ) {
				$right = '';
			}
			static::setQueryArrayValue($subkey . $right, $arr[ $key ], $value, $depth + 1);
			return;
		}
		$arr[ $key ][] = $value;
	}
}
