<?php

namespace WPDRMS\AdminUI\Options;

/**
 * Holds an ordered list of TaxonomyExclusionRule entries.
 * Stored as a plain-array structure so the serialized DB value has no class-name dependency.
 *
 * @phpstan-type TaxonomyExclusionRuleArrayOptionArgs array{logic?: string, rules: array<mixed>}
 * @extends AbstractOption<TaxonomyExclusionRuleArrayOptionArgs>
 */
class TaxonomyExclusionRuleArrayOption extends AbstractOption {
	protected array $defaults = array( 'logic' => 'AND', 'rules' => array() );

	public string $logic = 'AND';

	/** @var TaxonomyExclusionRule[] */
	public array $rules = array();

	public function __construct( array $args ) {
		parent::__construct( $args );
		$raw_logic   = $this->args['logic'] ?? 'AND';
		$this->logic = in_array( $raw_logic, array( 'AND', 'OR' ), true ) ? $raw_logic : 'AND';
		$this->rules = array_map(
			function ( $rule ) {
				return new TaxonomyExclusionRule( is_array( $rule ) ? $rule : (array) $rule );
			},
			$this->args['rules'] ?? array()
		);
	}

	/**
	 * @return array{logic: string, rules: array<int, array<string, mixed>>}
	 */
	public function jsonSerialize(): array {
		return array(
			'logic' => $this->logic,
			'rules' => array_map(
				function ( TaxonomyExclusionRule $r ) {
					return $r->jsonSerialize();
				},
				$this->rules
			),
		);
	}
}
