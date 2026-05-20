<?php
namespace WPDRMS\PluginCore\Traits;

/**
 * This can be used in Abstract classes, can handle heritage by storing the singleton data in an array
 */
trait SingletonTrait {
	/**
	 * Use a very unspecific name for this to prevent any conflicts of attribute names
	 *
	 * @var array<static>
	 */
	protected static $singleton__object_instances__array = array();

	/**
	 * @param mixed ...$args
	 * @return static
	 */
	final public static function getInstance( ...$args ) {
		$class = get_called_class();
		if ( !isset(static::$singleton__object_instances__array[ $class ]) ) {
			static::$singleton__object_instances__array[ $class ] = new $class(...$args);
		}
		return static::$singleton__object_instances__array[ $class ];
	}

	/**
	 * @param mixed ...$args
	 * @return static
	 */
	final public static function instance( ...$args ) {
		return static::getInstance( ...$args );
	}

	private function __construct() {}

	final public function __wakeup() {}

	final public function __clone() {}

	/**
	 * Resets the singleton instance — intended for use in unit tests only.
	 *
	 * @return void
	 */
	final public static function resetInstance(): void {
		$class = get_called_class();
		unset( static::$singleton__object_instances__array[ $class ] );
	}
}
