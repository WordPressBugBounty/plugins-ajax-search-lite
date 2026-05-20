<?php

namespace WPDRMS\AdminUI\Options;

class PriorityGroupsOption implements \JsonSerializable {
	public const VALID_PHRASE_LOGICS = array( 'disabled', 'any', 'exact', 'start', 'end' );
	public const VALID_LOGICS        = array( 'and', 'or' );

	public string $name         = '';
	public int    $priority     = 100;
	public string $phrase_logic = 'disabled';
	public string $phrase       = '';
	public int    $instance     = 0;
	public int    $blog_id      = 0;
	public string $logic        = 'and';
	public bool   $enabled      = true;
	public array  $rules        = array();

	public function __construct( array $data ) {
		$this->name         = isset( $data['name'] ) ? (string) $data['name'] : '';
		$this->priority     = isset( $data['priority'] ) ? (int) $data['priority'] : 100;
		$this->phrase_logic = in_array( $data['phrase_logic'] ?? '', self::VALID_PHRASE_LOGICS, true )
			? (string) $data['phrase_logic'] : 'disabled';
		$this->phrase   = isset( $data['phrase'] ) ? (string) $data['phrase'] : '';
		$this->instance = isset( $data['instance'] ) ? (int) $data['instance'] : 0;
		$this->blog_id  = isset( $data['blog_id'] ) ? (int) $data['blog_id'] : 0;
		$this->logic    = in_array( $data['logic'] ?? '', self::VALID_LOGICS, true )
			? (string) $data['logic'] : 'and';
		$this->enabled  = isset( $data['enabled'] ) ? (bool) $data['enabled'] : true;
		$this->rules    = isset( $data['rules'] ) && is_array( $data['rules'] ) ? $data['rules'] : array();
	}

	public function jsonSerialize(): array {
		return array(
			'name'         => $this->name,
			'priority'     => $this->priority,
			'phrase_logic' => $this->phrase_logic,
			'phrase'       => $this->phrase,
			'instance'     => $this->instance,
			'blog_id'      => $this->blog_id,
			'logic'        => $this->logic,
			'enabled'      => $this->enabled,
			'rules'        => $this->rules,
		);
	}
}
