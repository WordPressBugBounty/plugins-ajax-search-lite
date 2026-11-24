<?php

namespace WPDRMS\ASL\Utils;

/**
 * A language sent with a search request is only used when its multilingual plugin is active and it is
 * one of that plugin's languages. Otherwise it reached queries (the WPML translations table, missing
 * without WPML) and URLs (the Google suggestions) as it was.
 */
class RequestLanguage {
	/**
	 * @param mixed $code
	 */
	public static function isLanguageCode( $code ): bool {
		return is_string($code) && preg_match('/^[a-z]{2,3}(?:[_-][A-Za-z]{2,4})?$/D', $code) === 1;
	}

	/**
	 * @param mixed $code
	 * @return string The code when WPML is active and it is one of its languages, '' otherwise
	 */
	public static function wpml( $code ): string {
		if ( !is_string($code) || $code === '' || !defined('ICL_SITEPRESS_VERSION') ) {
			return '';
		}
		$languages = apply_filters('wpml_active_languages', null, array( 'skip_missing' => 0 ));
		return is_array($languages) && isset($languages[ $code ]) ? $code : '';
	}

	/**
	 * Whether WPML's language switches nest (WPML 5.0 and later): `switch_lang( $code )` opens a scope, even for
	 * the current language, and only `switch_lang( null )` closes one. Before 5.0 null rewinds to the request's
	 * first language instead. A switch that is restored picks its restore by this.
	 */
	public static function wpmlNests(): bool {
		return defined('ICL_SITEPRESS_VERSION') && version_compare( (string) ICL_SITEPRESS_VERSION, '5.0', '>=');
	}

	/**
	 * @param mixed $code
	 * @return string The code when Polylang is active and it is one of its languages, '' otherwise
	 */
	public static function polylang( $code ): string {
		if ( !is_string($code) || $code === '' || !function_exists('pll_languages_list') ) {
			return '';
		}
		return in_array($code, (array) pll_languages_list(array( 'fields' => 'slug' )), true) ? $code : '';
	}

	/**
	 * @param mixed $code
	 * @return string The code when qTranslate is active and it is one of its enabled languages, '' otherwise
	 */
	public static function qtranslate( $code ): string {
		global $q_config;
		if ( !is_string($code) || $code === '' || !function_exists('qtranxf_use') || !isset($q_config['enabled_languages']) ) {
			return '';
		}
		return in_array($code, (array) $q_config['enabled_languages'], true) ? $code : '';
	}
}
