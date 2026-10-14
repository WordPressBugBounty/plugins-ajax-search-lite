<?php

namespace WPDRMS\AdminUI\Options;

/**
 * A fragment of HTML (1.15.0): kept as given, then wp_kses_post'd, every time it is built, so a value is clean however
 * it was stored. Unlike StringOption it never goes through Str::anyToString(), which rewrites JSON-looking text
 * ('true' becomes ''); a non-scalar value reads as ''.
 */
class HtmlOption extends AbstractOption {
	protected array $defaults = array(
		'value' => '',
	);

	public string $value;

	public function __construct( array $args ) {
		parent::__construct($args);
		$value       = $this->args['value'];
		$this->value = is_scalar($value) ? wp_kses_post((string) $value) : '';
	}
}
