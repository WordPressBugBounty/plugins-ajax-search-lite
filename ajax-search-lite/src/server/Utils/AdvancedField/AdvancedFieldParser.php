<?php

namespace WPDRMS\ASL\Utils\AdvancedField;
\defined( 'ABSPATH' ) || exit; \defined( 'ASL_SCOPED' ) || ! \defined( 'ASL_PATH' ) || require_once ASL_PATH . 'vendor-prefixed/autoload.php';

use InvalidArgumentException;
use stdClass;
use AjaxSearchLite\Vendor\WPDRMS\PluginCore\Traits\SingletonTrait;
use AjaxSearchLite\Vendor\WPDRMS\Utils\Str;

class AdvancedFieldParser {
	use SingletonTrait;

	/**
	 * @var array{
	 *      callback: callable(null|array<string, mixed>, null|stdClass):void,
	 *      field_name: string,
	 *      field_title: string,
	 *      attributes?: Array<string, array{
	 *          type: 'text'|'textarea'|'number'|'color'|'select'|'multiselect'|'checkbox'
	 *      }>
	 *  } | array{}
	 */
	private array $handlers = array();

	private PostFieldTypeFactory $post_type_field_factory;
	private UserFieldTypeFactory $user_type_field_factory;

	/**
	 * The values substituted during the parse in progress, by placeholder. The template is the administrator's,
	 * the values are the posts' (every author writes them): a value is data, so a `{field}` or a `[shortcode]`
	 * inside one is never resolved. The template is scanned with placeholders in the values' place, and the values
	 * take their place last, after every pass and after do_shortcode().
	 *
	 * @var array<string, string>
	 */
	private array $values = array();

	private function __construct() {
		$this->post_type_field_factory = new PostFieldTypeFactory();
		$this->user_type_field_factory = new UserFieldTypeFactory();

		// In case of additional external types, this hooks should be used
		do_action('asl/utils/advanced-field', $this);
	}

	/**
	 * Adds a field with
	 *
	 * @param array{
	 *     field_name: string,
	 *     callback: callable,
	 *     type?: 'post_type'|'user'
	 * } $args
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function add( array $args ) {
		$field_args = $args;
		if ( !isset($field_args['field_name']) ) {
			throw new InvalidArgumentException('field_name is missing');
		}
		if ( !isset($field_args['callback']) || !is_callable($field_args['callback']) ) {
			throw new InvalidArgumentException('callback is missing or invalid');
		}
		$field_args['type'] = $field_args['type'] ?? 'pagepost';
		if ( $field_args['type'] === 'post_type' ) {
			$field_args['type'] = 'pagepost';
		}
		$this->handlers[ $args['field_name'] ] = $field_args;
	}

	/**
	 * Parses the advanced field based on the registered handlers
	 *
	 * Advanced fields can use the following syntax:
	 *
	 * {} curly braces including variable name. The registered handlers will look for these and replace
	 * with the processed text. Example: `{_price}` will become `$100`
	 *
	 * [] brackets to encapsulate field variables. When either of the variables are empty within the curly
	 * brackets, it is resolved to an empty string.
	 * Example: `[The price is {_price}]` will become `The price is 100$` or "" if the _price field resolves to ""
	 *
	 * [[]] double brackets to indicate shortcodes. Example `[[my_shortcode]]`
	 *
	 * @param string   $text
	 * @param stdClass $result
	 * @return string
	 */
	public function parse( string $text, stdClass $result ): string {
		// A handler may parse another template meanwhile
		$outer_values = $this->values;
		$this->values = array();
		try {
			$out = strtr( $this->process( stripslashes($text), $result ), $this->values );
		} finally {
			$this->values = $outer_values;
		}
		return $out;
	}

	/**
	 * The placeholder standing for a value until the parse is over, see $values
	 *
	 * @param string $value
	 */
	private function placeholder( string $value ): string {
		$placeholder                  = "\x1A" . 'asl-advanced-field-' . count($this->values) . "\x1A";
		$this->values[ $placeholder ] = $value;
		return $placeholder;
	}

	/**
	 * A recursive function to process the advanced title/content field values.
	 *
	 * @param string   $text
	 * @param stdClass $result
	 * @param bool     $return_on_empty_result
	 * @param int      $depth
	 * @return string
	 */
	private function process( string $text, stdClass $result, bool $return_on_empty_result = false, int $depth = 0 ): string {
		$field_pattern = $text; // Let's not make changes to arguments, shall we.

		// Handle shortcode patterns
		if ( $depth === 0 && strpos($field_pattern, '[[') !== false ) {
			$do_shortcodes = true;
			$field_pattern = str_replace(
				array( '[[', ']]' ),
				array( '____shortcode_start____', '____shortcode_end____' ),
				$field_pattern
			);
		} else {
			$do_shortcodes = false;
		}

		// Find conditional patterns, like [prefix {field} suffix}
		if ( preg_match_all( '/(\[.*?\])/', $text, $matches ) ) {
			foreach ( $matches[1] as $fieldset ) {
				$processed_fieldset = $this->process(
					str_replace(array( '[', ']' ), '', $fieldset),
					$result,
					true,
					$depth + 1
				);
				$field_pattern      = Str::replaceFirst($fieldset, $processed_fieldset, $field_pattern);
			}
		}

		if ( preg_match_all( '/{(.*?)}/', $field_pattern, $matches ) ) {
			foreach ( $matches[1] as $complete_field ) {
				$val        = '';
				$field_args = shortcode_parse_atts($complete_field);
				if ( is_array($field_args) && isset($field_args[0]) ) {
					$field = array_shift($field_args);
				} else {
					continue;
				}

				foreach ( $this->handlers as $field_name => $handler_args ) {
					if ( $field_name === $field && $handler_args['type'] === $result->content_type ) {
						$val = Str::anyToString( $handler_args['callback']($field_args, $result) );
					}
				}

				if ( $val === '' ) {
					if ( $result->content_type === 'pagepost' || $result->content_type === 'attachment' ) {
						$val = $this->post_type_field_factory->create($field, $field_args, $result)->process();
					} elseif ( $result->content_type === 'user' ) {
						$val = $this->user_type_field_factory->create($field, $field_args, $result)->process();
					}
					$val = apply_filters('asl/utils/advanced-field/raw_value', $val, $field, $result, $field_args);
				}

				// For the recursive call to break, if any of the fields is empty
				if ( $return_on_empty_result && trim($val) === '' ) {
					return '';
				}
				$val = Str::fixSSLURLs($val);

				// Only a numeric cap is one: a non-number (`maxlength=''`) would reach the cut as a string length,
				// a TypeError.
				if ( isset($field_args['maxlength']) && is_numeric($field_args['maxlength']) ) {
					$val = Str::truncateAtWord($val, (int) $field_args['maxlength']);
				}

				if ( $result->content_type === 'pagepost' || $result->content_type === 'attachment' ) {
					$val = apply_filters('asl_cpt_advanced_field_value', $val, $field, $result, $field_args);
				} elseif ( $result->content_type === 'user' ) {
					$val = apply_filters('asl_user_advanced_field_value', $val, $field, $result, $field_args);
				}
				$val = apply_filters('asl/utils/advanced-field/value', $val, $field, $result, $field_args);
				/*
				 * A snippet on the filters above may return null (a branch without `return`) or an array (ACF's
				 * get_field() on a checkbox field): such a value renders as an empty field instead of a TypeError.
				 * A number still renders.
				 */
				$val           = is_scalar($val) ? (string) $val : '';
				$field_pattern = str_replace( '{' . $complete_field . '}', $this->placeholder($val), $field_pattern );
			}
		}

		// On depth=0 and if tags were found $do_shortcodes is true
		if ( $do_shortcodes ) {
			$field_pattern = str_replace(
				array( '____shortcode_start____', '____shortcode_end____' ),
				array( '[', ']' ),
				$field_pattern
			);
			/*
			 * Only the template's own [[shortcodes]] run. A value written into a shortcode tag by the template
			 * ([[gallery ids={_ids}]]) is passed to it without brackets, so it can neither close that tag nor open
			 * another; every other value is still a placeholder while the shortcodes run, see $values.
			 */
			$bracketless   = str_replace(array( '[', ']' ), '', $this->values);
			$field_pattern = preg_replace_callback(
				'/\[[^\[\]]*\]/',
				function ( $tag ) use ( $bracketless ) {
					return strtr($tag[0], $bracketless);
				},
				$field_pattern
			);
			$field_pattern = do_shortcode($field_pattern);
		}

		return $field_pattern;
	}
}
