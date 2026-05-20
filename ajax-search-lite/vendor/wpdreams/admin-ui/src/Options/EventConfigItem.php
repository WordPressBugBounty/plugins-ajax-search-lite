<?php

namespace WPDRMS\AdminUI\Options;

use JsonSerializable;

class EventConfigItem implements JsonSerializable {
	public bool $active;
	public string $name;
	public string $action;
	/** @var array<array{key: string, value: string}> */
	public array $params = array();

	public function __construct( array $data ) {
		$this->active = (bool) ( $data['active'] ?? false );
		$this->name   = (string) ( $data['name'] ?? '' );
		$this->action = (string) ( $data['action'] ?? '' );
		$this->params = array_values(
			array_map(
				function ( $p ) {
					return array(
						'key'   => (string) ( $p['key'] ?? '' ),
						'value' => (string) ( $p['value'] ?? '' ),
					);
				},
				is_array( $data['params'] ?? null ) ? $data['params'] : array()
			) 
		);
	}

	public function jsonSerialize(): array {
		return array(
			'active' => $this->active,
			'name'   => $this->name,
			'action' => $this->action,
			'params' => $this->params,
		);
	}
}
