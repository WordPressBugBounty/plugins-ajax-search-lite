<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace WPDRMS\Utils;

if ( !defined('ABSPATH') ) {
	die('-1');
}

class Functions {
	/**
	 * Validates an uuidv4 string.
	 *
	 * @param string $key
	 * @return bool
	 */
	public static function uuidv4Validate( string $key ): bool {
		return !( preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $key) !== 1 );
	}

	public static function uv( string $key ): bool {
		return self::uuidv4Validate($key);
	}

	public static function getImageFromHTML( $content, $number = 0, $exclude = array() ) {
		if ( !is_string($content) || $content === '' || !class_exists('domDocument') ) {
			return false;
		}

		if ( function_exists('mb_encode_numericentity') ) {
			$encoded_content = mb_encode_numericentity(
				htmlspecialchars_decode(
					htmlentities( $content, ENT_NOQUOTES, 'UTF-8', false ),
					ENT_NOQUOTES
				),
				array( 0x80, 0x10FFFF, 0, ~0 ),
				'UTF-8'
			);
		} else {
			$encoded_content = $content;
		}

		if ( $encoded_content === '' ) {
			return false;
		}

		// The arguments expects 1 as the first image, while it is the 0th
		$number = intval($number) - 1;
		$number = $number < 0 ? 0 : $number;

		if ( !is_array($exclude) ) {
			$exclude = strval($exclude);
			$exclude = explode(',', $exclude);
		}
		foreach ( $exclude as $k => &$v ) {
			$v = trim($v);
			if ( $v === '' ) {
				unset($exclude[ $k ]);
			}
		}

		$elements   = array( 'img', 'div' );
		$attributes = array(
			'src',
			'data-src-fg',
			'data-img',
			'data-image',
			'data-thumbnail',
			'data-thumb',
			'data-imgsrc',
		);
		$im         = '';

		foreach ( $elements as $element ) {
			$dom = new \domDocument();
			/**
			 * The libxml_use_internal_errors & libxml_clear_errors solutions
			 * seem not to work on some servers, so we are stuck using @ operator instead.
			 */
			@$dom->loadHTML($encoded_content); // phpcs:ignore
			$dom->preserveWhiteSpace = false;  // phpcs:ignore
			@$images                 = $dom->getElementsByTagName($element); // phpcs:ignore
			if ( $images->length > 0 ) {
				$get = $images->length > $number ? $number : 0;
				for ( $i =$get;$i <$images->length;$i++ ) {
					foreach ( $attributes as $att ) {
						$im = $images->item($i)->getAttribute($att);
						if ( !empty($im) ) {
							break;
						}
					}
					foreach ( $exclude as $ex ) {
						if ( strpos($im, $ex) !== false ) {
							$im = '';
							continue 2;
						}
					}
					break;
				}
				if ( $im !== '' ) {
					return $im;
				}
			}
		}

		// Still no image
		return false;
	}
}
