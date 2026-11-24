<?php

namespace AjaxSearchLite\Vendor\WPDRMS\PluginCore\Traits;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use TypeError;

/**
 * Assigns a value that code we do not control produced (a filter, a customer snippet, a database row) to a
 * declared typed property without ending the request with a TypeError (wpdreams/ajax-search-pro#523).
 *
 * PHP's own coercion is kept: '5' still becomes 5 on an int property, as before. Only a value PHP refuses
 * is handled, and only where nothing is lost:
 * - null on a non-nullable property: the property's declared default, when it has one;
 * - a scalar on an array property: wrapped in a one-element array;
 * - anything else: the assignment is skipped, the property keeps its value, and _doing_it_wrong() names it.
 *
 * The happy path is a plain assignment; reflection runs only after PHP refused the value.
 */
trait TypedPropertyAssignmentTrait {
	/**
	 * @param string $property A property declared on this object.
	 * @param mixed  $value
	 * @return bool False when the value was skipped.
	 */
	private function assignTypedProperty( string $property, $value ): bool {
		try {
			$this->{$property} = $value;
			return true;
		} catch ( TypeError $e ) {
			$type = ( new ReflectionProperty($this, $property) )->getType();

			if ( $value === null ) {
				$defaults = ( new ReflectionClass($this) )->getDefaultProperties();
				if ( array_key_exists($property, $defaults) ) {
					$this->{$property} = $defaults[ $property ];
					return true;
				}
			} elseif ( is_scalar($value) && self::typedPropertyAccepts($type, 'array') ) {
				$this->{$property} = array( $value );
				return true;
			}

			if ( function_exists('_doing_it_wrong') ) {
				_doing_it_wrong(
					esc_html(get_class($this) . '::$' . $property),
					esc_html(
						sprintf(
							'A value of type %s cannot be stored in a property of type %s, so it was ignored.',
							is_object($value) ? get_class($value) : gettype($value),
							self::typedPropertyTypeName($type)
						)
					),
					'1.8.0'
				);
			}
			return false;
		}
	}

	/**
	 * The type as written, e.g. "?int" or "array|string". Not (string) $type: ReflectionType::__toString() is
	 * deprecated on PHP 7.4 and would print a notice.
	 *
	 * @param ReflectionType|null $type
	 */
	private static function typedPropertyTypeName( ?ReflectionType $type ): string {
		if ( $type instanceof ReflectionNamedType ) {
			$name = $type->getName();
			return ( $type->allowsNull() && $name !== 'mixed' && $name !== 'null' ? '?' : '' ) . $name;
		}
		if ( $type !== null && method_exists($type, 'getTypes') ) {
			$names = array();
			foreach ( $type->getTypes() as $member ) {
				$names[] = $member instanceof ReflectionNamedType ? $member->getName() : 'mixed';
			}
			return implode('|', $names);
		}
		return 'mixed';
	}

	/**
	 * @param ReflectionType|null $type
	 * @param string              $name A builtin type name.
	 */
	private static function typedPropertyAccepts( ?ReflectionType $type, string $name ): bool {
		if ( $type instanceof ReflectionNamedType ) {
			return $type->getName() === $name;
		}
		// A union type (PHP 8): ReflectionUnionType::getTypes(). Checked by method, as the class is not on PHP 7.4.
		if ( $type !== null && method_exists($type, 'getTypes') ) {
			foreach ( $type->getTypes() as $member ) {
				if ( $member instanceof ReflectionNamedType && $member->getName() === $name ) {
					return true;
				}
			}
		}
		return false;
	}
}
