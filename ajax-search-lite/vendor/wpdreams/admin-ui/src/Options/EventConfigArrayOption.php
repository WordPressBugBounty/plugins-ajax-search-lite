<?php

namespace WPDRMS\AdminUI\Options;

/**
 * Holds an ordered list of EventConfigItem entries.
 * Stored as a plain-array structure so the serialized DB value has no class-name dependency.
 *
 * @phpstan-type EventConfigArrayOptionArgs array{items: array<mixed>}
 * @extends AbstractOption<EventConfigArrayOptionArgs>
 */
class EventConfigArrayOption extends AbstractOption {
	protected array $defaults = array( 'items' => array() );

	/** @var EventConfigItem[] */
	public array $items = array();

	public function __construct( array $args ) {
		parent::__construct($args);
		$this->items = array_map(
			function ( $item ) {
				return new EventConfigItem(is_array($item) ? $item : (array) $item);
			},
			$this->args['items'] ?? array()
		);
	}

	/**
	 * Override to ensure items are stored as plain arrays, not class instances.
	 *
	 * @return array{items: array<int, array<string, mixed>>}
	 */
	public function jsonSerialize(): array {
		return array(
			'items' => array_map(
				function ( EventConfigItem $item ) {
					return $item->jsonSerialize();
				},
				$this->items
			),
		);
	}
}
