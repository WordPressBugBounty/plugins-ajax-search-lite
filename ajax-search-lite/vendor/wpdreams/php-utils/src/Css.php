<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace WPDRMS\Utils;

if ( !defined('ABSPATH') ) {
	die('-1');
}

class Css {

	/**
	 * Generates a media query or a class around the CSS code.
	 *
	 * When $preview is true, wraps the CSS as: .wpd-preview-$screen { $css }.
	 * Otherwise it returns a media queried based on the min and max width parameters.
	 *
	 * @param string                     $css
	 * @param 'desktop'|'tablet'|'phone' $screen
	 * @param int                        $min_width
	 * @param int                        $max_width
	 * @param bool|null                  $preview When true, generates nested css
	 * @return string
	 */
	public static function getCssForScreen(
		string $css,
		string $screen = 'tablet',
		int $min_width = 0,
		int $max_width = 1024,
		?bool $preview = false
	): string {
		$trimmed_css = trim( preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $css) );
		if ( $trimmed_css === '' ) {
			return '';
		}
		if ( !empty($preview) ) {
			return "
			.wpd-preview-$screen {
				$trimmed_css
			}
			";
		}
		if ( $min_width === 0 ) {
			return "
			@media only screen and (max-width: {$max_width}px) {
				$trimmed_css
			}
			";
		} else {
			return "
			@media only screen and (min-width: {$min_width}px) and (max-width: {$max_width}px) {
				$trimmed_css
			}
			";
		}
	}

	/**
	 * Helper method to be used before printing the font styles. Converts font families to apostrophed versions.
	 *
	 * @param string $font
	 * @return mixed
	 */
	public static function font( string $font ) {
		preg_match('/family:(.*?);/', $font, $fonts);
		if ( isset($fonts[1]) ) {
			$f = explode(',', str_replace(array( '"', "'" ), '', $fonts[1]));
			foreach ( $f as &$_f ) {
				if ( trim($_f) !== 'inherit' ) {
					$_f = '"' . trim($_f) . '"';
				} else {
					$_f = trim($_f);
				}
			}
			$f   = implode(',', $f);
			$ret = preg_replace('/family:(.*?);/', 'family:' . $f . ';', $font);
		} else {
			$ret = $font;
		}

		return apply_filters('wpdrms/utils/css/font', $ret);
	}

	public static function minify( $css ) {
		// Normalize whitespace
		$css = preg_replace( '/\s+/', ' ', $css );
		// Remove spaces before and after comment
		$css = preg_replace( '/(\s+)(\/\*(.*?)\*\/)(\s+)/', '$2', $css );
		// Remove comment blocks, everything between /* and */, unless
		// preserved with /*! ... */ or /** ... */
		$css = preg_replace( '~/\*(?![\!|\*])(.*?)\*/~', '', $css );
		// Remove space after , : ; { } */ >
		$css = preg_replace( '/(,|:|;|\{|}|\*\/|>) /', '$1', $css );
		// Remove space before , ; { } ( ) >
		$css = preg_replace( '/ (,|;|\{|}|\(|\)|>)/', '$1', $css );
		// Add back the space for media queries operator
		$css = preg_replace( '/and\(/', 'and (', $css );
		// Strips leading 0 on decimal values (converts 0.5px into .5px)
		$css = preg_replace( '/(:| )0\.([0-9]+)(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}.${2}${3}', $css );
		// Strips units if value is 0 (converts 0px to 0)
		$css = preg_replace( '/(:| )(\.?)0(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}0', $css );
		// Converts all zeros value into short-hand
		$css = preg_replace( '/0 0 0 0;/', '0;', $css );
		$css = preg_replace( '/0 0 0 0\}/', '0}', $css );
		// Invisible inset box shadow
		$css = preg_replace( '/box-shadow:0 0 0(?: 0)? [a-fA-F0-9()#,rgb]+(?: inset)?([};])/i', 'box-shadow:none${1}', $css );
		// Transparent box shadow
		$css = preg_replace( '/box-shadow:[0-9px ]+ (transparent inset|transparent)([};])/i', 'box-shadow:none${2}', $css );
		// Invisible text shadow
		$css = preg_replace( '/text-shadow:0 0(?: 0)? [a-fA-F0-9()#,rgb]+([};])/i', 'text-shadow:none${1}', $css );
		// Transparent text shadow
		$css = preg_replace( '/text-shadow:[0-9px ]+ transparent([};])/i', 'text-shadow:none${1}', $css );
		// Shorten 6-character hex color codes to 3-character where possible
		$css = preg_replace( '/#([a-f0-9])\\1([a-f0-9])\\2([a-f0-9])\\3/i', '#\1\2\3', $css );
		// Remove ; before }
		$css = preg_replace( '/;(?=\s*})/', '', $css );
		return trim( $css );
	}

	public static function any2hex( string $color ): string {
		// Expand short hex: #RGB → #RRGGBB, #RGBA → #RRGGBBAA
		if ( preg_match( '/^#([0-9a-fA-F])([0-9a-fA-F])([0-9a-fA-F])([0-9a-fA-F])?$/', $color, $m ) ) {
			$hex = '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3];
			if ( isset($m[4]) && $m[4] !== '' ) {
				$hex .= $m[4] . $m[4];
			}
			return strtoupper( $hex );
		}

		if ( strlen($color) > 7 ) {
			// rgb / rgba
			preg_match( '/rgba?\s*\(\s*(\d+),\s*(\d+),\s*(\d+)(?:,\s*([0-9.]+))?\s*\)/', $color, $c );
			if ( !empty($c) ) {
				$hex = '#' . sprintf('%02X', $c[1]) . sprintf('%02X', $c[2]) . sprintf('%02X', $c[3]);
				if ( isset($c[4]) && $c[4] !== '' ) {
					$hex .= sprintf('%02X', min(255, (int) round( (float) $c[4] * 255)));
				}
				return $hex;
			}

			// hsl / hsla
			preg_match( '/hsla?\s*\(\s*([0-9.]+),\s*([0-9.]+)%?,\s*([0-9.]+)%?(?:,\s*([0-9.]+))?\s*\)/', $color, $c );
			if ( !empty($c) ) {
				[ $r, $g, $b ] = self::hslToRgb( (float) $c[1], (float) $c[2], (float) $c[3] );
				$hex           = '#' . sprintf('%02X', $r) . sprintf('%02X', $g) . sprintf('%02X', $b);
				if ( isset($c[4]) && $c[4] !== '' ) {
					$hex .= sprintf('%02X', min(255, (int) round( (float) $c[4] * 255)));
				}
				return $hex;
			}
		}

		return $color;
	}

	public static function any2rgb( string $color ): string {
		// Expand short hex: #RGB → #RRGGBB, #RGBA → #RRGGBBAA
		if ( preg_match( '/^#([0-9a-fA-F])([0-9a-fA-F])([0-9a-fA-F])([0-9a-fA-F])?$/', $color, $m ) ) {
			$color = '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3];
			if ( isset($m[4]) && $m[4] !== '' ) {
				$color .= $m[4] . $m[4];
			}
		}

		// Full hex: #RRGGBB or #RRGGBBAA
		if ( preg_match( '/^#([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})?$/i', $color, $m ) ) {
			$r = hexdec( $m[1] );
			$g = hexdec( $m[2] );
			$b = hexdec( $m[3] );
			if ( isset($m[4]) && $m[4] !== '' ) {
				$a = round( hexdec($m[4]) / 255, 3 );
				return "rgba($r, $g, $b, $a)";
			}
			return "rgb($r, $g, $b)";
		}

		// rgb / rgba passthrough
		if ( preg_match( '/^rgba?\s*\(/', $color ) ) {
			return $color;
		}

		// hsl / hsla
		preg_match( '/hsla?\s*\(\s*([0-9.]+),\s*([0-9.]+)%?,\s*([0-9.]+)%?(?:,\s*([0-9.]+))?\s*\)/', $color, $c );
		if ( !empty($c) ) {
			[ $r, $g, $b ] = self::hslToRgb( (float) $c[1], (float) $c[2], (float) $c[3] );
			if ( isset($c[4]) && $c[4] !== '' ) {
				return "rgba($r, $g, $b, {$c[4]})";
			}
			return "rgb($r, $g, $b)";
		}

		return $color;
	}

	private static function hslToRgb( float $h, float $s, float $l ): array {
		$h = fmod($h, 360);
		if ( $h < 0 ) {
			$h += 360; }
		$s /= 100;
		$l /= 100;
		$c  = ( 1 - abs(2 * $l - 1) ) * $s;
		$x  = $c * ( 1 - abs(fmod($h / 60, 2) - 1) );
		$m  = $l - $c / 2;

		if ( $h < 60 ) {
			$r = $c;
			$g = $x;
			$b = 0;
		} elseif ( $h < 120 ) {
			$r = $x;
			$g = $c;
			$b = 0;
		} elseif ( $h < 180 ) {
			$r = 0;
			$g = $c;
			$b = $x;
		} elseif ( $h < 240 ) {
			$r = 0;
			$g = $x;
			$b = $c;
		} elseif ( $h < 300 ) {
			$r = $x;
			$g = 0;
			$b = $c;
		} else {
			$r = $c;
			$g = 0;
			$b = $x;
		}

		return array(
			(int) round(( $r + $m ) * 255),
			(int) round(( $g + $m ) * 255),
			(int) round(( $b + $m ) * 255),
		);
	}
}
