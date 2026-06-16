<?php

namespace WPDRMS\AdminUI\Options;

/**
 * A SelectOption pre-populated with a broad set of ISO 639-1 language codes, for
 * language-aware / AI features. The empty string '' represents "no specific
 * language" (e.g. site default / let the AI infer).
 *
 * Codes are used as the values because that matches the convention used by
 * multilingual plugins (Polylang/WPML) and is understood by the AI gateway
 * (its prompts accept a language code such as 'en'/'de'). An invalid stored
 * value falls back to '' via SelectOption's default-value behaviour.
 */
class LanguageSelectOption extends SelectOption {

	/**
	 * Allowed values: '' (none) plus ISO 639-1 codes (a few region variants included).
	 *
	 * @var string[]
	 */
	public const LANGUAGES = array(
		'',
		'en',
		'es',
		'pt',
		'pt-BR',
		'fr',
		'de',
		'it',
		'nl',
		'da',
		'sv',
		'no',
		'fi',
		'is',
		'pl',
		'cs',
		'sk',
		'hu',
		'ro',
		'bg',
		'hr',
		'sr',
		'sl',
		'el',
		'ru',
		'uk',
		'tr',
		'ar',
		'he',
		'fa',
		'hi',
		'bn',
		'ur',
		'ta',
		'te',
		'th',
		'vi',
		'id',
		'ms',
		'tl',
		'zh',
		'zh-TW',
		'ja',
		'ko',
		'sw',
		'af',
		'ca',
		'eu',
		'gl',
		'et',
		'lv',
		'lt',
	);

	/**
	 * @param array<string, mixed> $args
	 */
	public function __construct( array $args ) {
		$args['options'] = self::LANGUAGES;
		if ( !isset($args['default_value']) ) {
			$args['default_value'] = '';
		}
		parent::__construct($args);
	}
}
