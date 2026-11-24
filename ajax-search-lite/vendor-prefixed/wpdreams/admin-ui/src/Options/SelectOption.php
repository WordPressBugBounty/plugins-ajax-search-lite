<?php

namespace AjaxSearchLite\Vendor\WPDRMS\AdminUI\Options;

use AjaxSearchLite\Vendor\WPDRMS\Utils\Str;

/**
 * @phpstan-type SelectOptionArgs array{
 *     value: string,
 *     default_value: string,
 *     options: string[],
 * }
 * @extends AbstractOption<SelectOptionArgs>
 */
class SelectOption extends AbstractOption {
	protected array $defaults = array(
		'value'         => '',
		'default_value' => '',
		'options'       => array(),
	);

	public string $value;

	public function __construct( array $args ) {
		parent::__construct($args);
		$this->value = Str::anyToString($this->args['value']);
		if ( !in_array($this->value, array_map(fn( $v )=>Str::anyToString($v), $this->argList('options')), true) ) {
			$this->value = $this->argString('default_value');
		}
	}
}
