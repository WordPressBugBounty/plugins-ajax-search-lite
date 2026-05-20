<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace WPDRMS\Utils;

if ( !defined('ABSPATH') ) {
	die('-1');
}

class Languages {
	/**
	 * Returns active WPML/Polylang languages as [code => label] map.
	 * Returns an empty array when no multilingual plugin is active.
	 *
	 * @return array<string, string>
	 */
	public static function getPluginLanguages(): array {
		$langs = array();

		if ( function_exists('pll_languages_list') ) {
			$_langs = pll_languages_list(array( 'fields' => 'slug' ));
			if ( is_array($_langs) ) {
				foreach ( $_langs as $_lang ) {
					$langs[ (string) $_lang ] = (string) $_lang;
				}
			}
		} elseif ( function_exists('icl_get_languages') ) {
			$_langs = icl_get_languages('skip_missing=0&orderby=KEY&order=DIR&link_empty_to=str');
			if ( is_array($_langs) ) {
				foreach ( array_reverse($_langs) as $_lang ) {
					$langs[ (string) $_lang['language_code'] ] = (string) $_lang['translated_name'];
				}
			}
		}

		return $langs;
	}
}
