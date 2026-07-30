<?php

namespace WPDRMS\AdminUI\Options;

/**
 * Multi-choice value from a fixed vocabulary: keeps the input items that are
 * part of `options` (deduplicated, input order preserved) and falls back to
 * `default_value` when nothing valid remains — the multi-value counterpart
 * of SelectOption's invalid→default rule.
 *
 * @phpstan-type MultiSelectOptionArgs array{
 *     value: string[],
 *     default_value: string[],
 *     options: string[],
 * }
 * @extends AbstractOption<MultiSelectOptionArgs>
 */
class MultiSelectOption extends AbstractOption {
	protected array $defaults = array(
		'value'         => array(),
		'default_value' => array(),
		'options'       => array(),
	);

	/**
	 * @var string[]
	 */
	public array $value;

	/**
	 * @param Array<string, mixed> $args
	 */
	public function __construct( array $args ) {
		parent::__construct($args);
		$raw = $this->args['value'];
		if ( is_string($raw) ) {
			$raw = array_values(array_filter(array_map('trim', explode(',', $raw))));
		} elseif ( !is_array($raw) ) {
			$raw = array();
		}
		$allowed = array_map('strval', $this->args['options']);
		$value   = array();
		foreach ( $raw as $item ) {
			if ( !is_scalar($item) ) {
				continue;
			}
			$item = (string) $item;
			if ( in_array($item, $allowed, true) && !in_array($item, $value, true) ) {
				$value[] = $item;
			}
		}
		$this->value = count($value) > 0 ? $value : array_map('strval', $this->args['default_value']);
	}
}
