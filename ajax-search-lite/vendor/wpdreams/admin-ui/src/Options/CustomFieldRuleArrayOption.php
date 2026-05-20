<?php

namespace WPDRMS\AdminUI\Options;

/**
 * Holds an ordered list of CustomFieldRuleItem entries.
 * Stored as a plain-array structure so the serialized DB value has no class-name dependency.
 *
 * @phpstan-type CustomFieldRuleArrayOptionArgs array{logic?: string, rules: array<mixed>}
 * @extends AbstractOption<CustomFieldRuleArrayOptionArgs>
 */
class CustomFieldRuleArrayOption extends AbstractOption {
	protected array $defaults = array( 'logic' => 'AND', 'rules' => array() );

	public string $logic = 'AND';

	/** @var CustomFieldRuleItem[] */
	public array $rules = array();

	public function __construct( array $args ) {
		parent::__construct($args);
		$raw_logic   = $this->args['logic'] ?? 'AND';
		$this->logic = in_array( $raw_logic, array( 'AND', 'OR' ), true ) ? $raw_logic : 'AND';
		$this->rules = array_map(
			function ( $rule ) {
				return new CustomFieldRuleItem(is_array($rule) ? $rule : (array) $rule);
			},
			$this->args['rules'] ?? array()
		);
	}

	/**
	 * Override to ensure rules are stored as plain arrays, not class instances.
	 *
	 * @return array{logic: string, rules: array<int, array<string, mixed>>}
	 */
	public function jsonSerialize(): array {
		return array(
			'logic' => $this->logic,
			'rules' => array_map(
				function ( CustomFieldRuleItem $r ) {
					return $r->jsonSerialize();
				},
				$this->rules
			),
		);
	}
}
