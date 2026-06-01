<?php

namespace WPDRMS\AdminUI\Options;

use WPDRMS\Utils\Str;

/**
 * @phpstan-type StringArrayOptionArgs array{
 *     value: string[],
 *     default_value: string[],
 * }
 * @extends AbstractOption<StringArrayOptionArgs>
 */
class StringArrayOption extends AbstractOption {
	protected array $defaults = array(
		'value'         => array(),
		'default_value' => array(),
	);

	/**
	 * @var string[]
	 */
	public array $value;

	public function __construct( array $args ) {
		parent::__construct($args);
		$raw = $this->args['value'];
		if ( is_string( $raw ) ) {
			$raw = array_values( array_filter( array_map( 'trim', explode( ',', $raw ) ) ) );
		} elseif ( ! is_array( $raw ) ) {
			$raw = array();
		}
		$this->value = array_map( fn ( $v ) => Str::anyToString( $v ), $raw );
	}
}
