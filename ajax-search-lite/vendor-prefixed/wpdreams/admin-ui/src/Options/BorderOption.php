<?php

namespace AjaxSearchLite\Vendor\WPDRMS\AdminUI\Options;

/**
 * @phpstan-type BorderOptionArgs array{
 *     width: int,
 *     color: 'red'|'blue'
 * }
 * @extends AbstractOption<BorderOptionArgs>
 */
class BorderOption extends AbstractOption {
	protected array $defaults = array(
		'width' => 1,
		'color' => 'red',
	);

	public int $width = 0;

	public string $color = 'red';

	public function __construct( array $args ) {
		parent::__construct($args);
		$this->width = $this->argInt('width');
		$this->color = $this->argString('color');
	}
}
