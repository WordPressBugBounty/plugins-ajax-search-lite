<?php

namespace WPDRMS\AdminUI\Options;

use WPDRMS\Utils\Str;

/**
 * @phpstan-type StringOptionArgs array{
 *     value: string,
 * }
 * @extends AbstractOption<StringOptionArgs>
 */
class StringOption extends AbstractOption {
	protected array $defaults = array(
		'value' => '',
	);

	public string $value;

	public function __construct( array $args ) {
		parent::__construct($args);
		$this->value = Str::anyToString($this->args['value']);
	}
}
