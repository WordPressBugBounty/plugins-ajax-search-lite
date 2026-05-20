<?php

namespace WPDRMS\AdminUI\Options;

use JsonSerializable;

class CustomFieldRuleItem implements JsonSerializable {
	/** @var string[] */
	public const ALLOWED_FIELD_TYPES = array( 'text', 'number', 'date', 'boolean', 'array' );

	/** @var string[] */
	public const ALLOWED_OPERATORS = array(
		'equals',
		'not_equals',
		'contains',
		'not_contains',
		'starts_with',
		'ends_with',
		'greater_than',
		'less_than',
		'is_empty',
		'is_not_empty',
	);

	public string $id;
	public string $field_key;
	public string $field_type;
	public string $operator;
	public string $value;

	public function __construct( array $data ) {
		$field_type       = (string) ( $data['field_type'] ?? 'text' );
		$operator         = (string) ( $data['operator'] ?? 'equals' );
		$this->id         = (string) ( $data['id'] ?? '' );
		$this->field_key  = sanitize_key( (string) ( $data['field_key'] ?? '' ) );
		$this->field_type = in_array( $field_type, self::ALLOWED_FIELD_TYPES, true ) ? $field_type : 'text';
		$this->operator   = in_array( $operator, self::ALLOWED_OPERATORS, true ) ? $operator : 'equals';
		$this->value      = (string) ( $data['value'] ?? '' );
	}

	public function jsonSerialize(): array {
		return array(
			'id'         => $this->id,
			'field_key'  => $this->field_key,
			'field_type' => $this->field_type,
			'operator'   => $this->operator,
			'value'      => $this->value,
		);
	}
}
