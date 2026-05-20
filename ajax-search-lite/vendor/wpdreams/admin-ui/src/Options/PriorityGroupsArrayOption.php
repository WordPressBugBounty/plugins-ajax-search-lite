<?php

namespace WPDRMS\AdminUI\Options;

class PriorityGroupsArrayOption extends AbstractOption {
	protected array $defaults = array( 'groups' => array() );

	/**
	 * @var PriorityGroupsOption[]
	 */
	public array $groups = array();

	public function __construct( array $args ) {
		parent::__construct( $args );
		$this->groups = array_map(
			static function ( $g ) {
				return new PriorityGroupsOption( is_array( $g ) ? $g : (array) $g );
			},
			$this->args['groups'] ?? array()
		);
	}

	public function jsonSerialize(): array {
		return array(
			'groups' => array_map( static fn( $g ) => $g->jsonSerialize(), $this->groups ),
		);
	}
}
