<?php

namespace WPDRMS\AdminUI\Options;

class PriorityGroupsOption implements \JsonSerializable {
	public const VALID_PHRASE_LOGICS = array( 'disabled', 'any', 'exact', 'start', 'end' );
	public const VALID_LOGICS        = array( 'and', 'or' );

	public string $name         = '';
	public int $priority        = 100;
	public string $phrase_logic = 'disabled';
	public string $phrase       = '';
	public int $instance        = 0;
	public int $blog_id         = 0;
	public string $logic        = 'and';
	public bool $enabled        = true;
	public array $rules         = array();

	/**
	 * Logic combining the "Applies when" gate condition types (and / or).
	 *
	 * @var string
	 */
	public string $condition_logic = 'and';

	/**
	 * Flexible "Applies when" gate — a list of typed condition rows (instance / phrase / taxonomy /
	 * meta). The legacy `instance`/`phrase`/`phrase_logic` fields above are retained for backward
	 * compatibility (the search-time evaluator falls back to them when this is empty).
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public array $conditions = array();

	/**
	 * What the group boosts. A list of typed boost rows, each with its own `priority` and matcher
	 * (`rule` = tax/cf/post matchers combined by `rule_logic`; `ordered_posts` = an ordered post
	 * list that expands to descending priorities). The legacy group-level `priority`/`logic`/`rules`
	 * fields above are retained for backward compatibility (used when this is empty).
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public array $boosts = array();

	public function __construct( array $data ) {
		$this->name         = isset( $data['name'] ) ? (string) $data['name'] : '';
		$this->priority     = isset( $data['priority'] ) ? (int) $data['priority'] : 100;
		$this->phrase_logic = in_array( $data['phrase_logic'] ?? '', self::VALID_PHRASE_LOGICS, true )
			? (string) $data['phrase_logic'] : 'disabled';
		$this->phrase       = isset( $data['phrase'] ) ? (string) $data['phrase'] : '';
		$this->instance     = isset( $data['instance'] ) ? (int) $data['instance'] : 0;
		$this->blog_id      = isset( $data['blog_id'] ) ? (int) $data['blog_id'] : 0;
		$this->logic        = in_array( $data['logic'] ?? '', self::VALID_LOGICS, true )
			? (string) $data['logic'] : 'and';
		$this->enabled      = isset( $data['enabled'] ) ? (bool) $data['enabled'] : true;
		$this->rules        = isset( $data['rules'] ) && is_array( $data['rules'] ) ? $data['rules'] : array();

		$this->condition_logic = in_array( $data['condition_logic'] ?? '', self::VALID_LOGICS, true )
			? (string) $data['condition_logic'] : 'and';
		$this->conditions      = isset( $data['conditions'] ) && is_array( $data['conditions'] ) ? $data['conditions'] : array();
		$this->boosts          = isset( $data['boosts'] ) && is_array( $data['boosts'] ) ? $data['boosts'] : array();
	}

	public function jsonSerialize(): array {
		return array(
			'name'            => $this->name,
			'priority'        => $this->priority,
			'phrase_logic'    => $this->phrase_logic,
			'phrase'          => $this->phrase,
			'instance'        => $this->instance,
			'blog_id'         => $this->blog_id,
			'logic'           => $this->logic,
			'enabled'         => $this->enabled,
			'rules'           => $this->rules,
			'condition_logic' => $this->condition_logic,
			'conditions'      => $this->conditions,
			'boosts'          => $this->boosts,
		);
	}
}
