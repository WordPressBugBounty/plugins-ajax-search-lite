<?php

namespace AjaxSearchLite\Vendor\WPDRMS\AdminUI\Options;

use JsonSerializable;
use AjaxSearchLite\Vendor\WPDRMS\PluginCore\Traits\JsonSerializablePublicProperties;
use AjaxSearchLite\Vendor\WPDRMS\Utils\ArrayUtils;

/**
 * @template T of Array<string, mixed>
 */
abstract class AbstractOption implements Option, JsonSerializable {
	use JsonSerializablePublicProperties;

	/**
	 * @var T
	 */
	protected array $defaults = array();

	/**
	 * The arguments from the constructor, merged and corrected with the defaults
	 *
	 * @var T
	 */
	protected array $args = array();

	/**
	 * @param Array<string, mixed> $args
	 */
	public function __construct( array $args ) {
		$this->args = ArrayUtils::arrayMergeRecursiveDistinct($this->defaults, $args);
	}

	/*
	 * Readers for one arg in the shape the option needs (wpdreams/ajax-search-pro#523). Stored option data can hold
	 * anything a backup, a migration or a filter left there; an option must not end the request over it.
	 */

	/**
	 * The arg as an array. An object gives its public properties; a non-empty scalar is wrapped when $wrap_scalar
	 * is true (a single value where a list is expected); anything else is an empty array.
	 *
	 * @param string $key         The arg's key.
	 * @param bool   $wrap_scalar Wrap a non-empty scalar in an array.
	 * @return array<array-key, mixed>
	 */
	protected function argList( string $key, bool $wrap_scalar = true ): array {
		$value = $this->args[ $key ] ?? null;
		if ( is_array($value) ) {
			return $value;
		}
		if ( is_object($value) ) {
			return get_object_vars($value);
		}
		if ( $wrap_scalar && is_scalar($value) && $value !== '' ) {
			return array( $value );
		}
		return array();
	}

	/**
	 * The entries of a list of records (rules, items, groups) that can be one: arrays and objects. A scalar entry
	 * would become a record of defaults through (array) $entry, so it is dropped. Reindexed: a list with a gap in
	 * its keys would be JSON-encoded as an object, and the admin screens read these as arrays.
	 *
	 * @param array<array-key, mixed> $entries
	 * @return list<array<array-key, mixed>|object>
	 */
	protected static function itemsOnly( array $entries ): array {
		return array_values(
			array_filter(
				$entries,
				static function ( $entry ) {
					return is_array($entry) || is_object($entry);
				}
			)
		);
	}

	/**
	 * The arg as an int when it is numeric (or a bool, which PHP accepted before); otherwise the option's default.
	 *
	 * @param string $key The arg's key.
	 */
	protected function argInt( string $key ): int {
		$value = $this->args[ $key ] ?? null;
		if ( is_numeric($value) || is_bool($value) ) {
			return (int) $value;
		}
		$default = $this->defaults[ $key ] ?? 0;
		return is_numeric($default) ? (int) $default : 0;
	}

	/**
	 * The arg as a string when it is a scalar; otherwise the option's default.
	 *
	 * @param string $key The arg's key.
	 */
	protected function argString( string $key ): string {
		$value = $this->args[ $key ] ?? null;
		if ( is_scalar($value) ) {
			return (string) $value;
		}
		$default = $this->defaults[ $key ] ?? '';
		return is_scalar($default) ? (string) $default : '';
	}

	public function value(): array {
		return $this->publicProperties();
	}
}
