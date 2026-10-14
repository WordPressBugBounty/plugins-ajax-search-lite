<?php

namespace WPDRMS\AdminUI;

use WPDRMS\AdminUI\Options\BoolOption;
use WPDRMS\AdminUI\Options\BorderOption;
use WPDRMS\AdminUI\Options\BoxShadowOption;
use WPDRMS\AdminUI\Options\DateTimeOption;
use WPDRMS\AdminUI\Options\HtmlOption;
use WPDRMS\AdminUI\Options\DirectoryListOption;
use WPDRMS\AdminUI\Options\CustomFieldRuleArrayOption;
use WPDRMS\AdminUI\Options\EventConfigArrayOption;
use WPDRMS\AdminUI\Options\PriorityGroupsArrayOption;
use WPDRMS\AdminUI\Options\TaxonomyExclusionRuleArrayOption;
use WPDRMS\AdminUI\Options\IntArrayOption;
use WPDRMS\AdminUI\Options\IntOption;
use WPDRMS\AdminUI\Options\LanguageSelectOption;
use WPDRMS\AdminUI\Options\Option;
use WPDRMS\AdminUI\Options\SelectOption;
use WPDRMS\AdminUI\Options\StringArrayOption;
use WPDRMS\AdminUI\Options\StringOption;
use WPDRMS\PluginCore\Traits\SingletonTrait;
use InvalidArgumentException;
use JsonSerializable;
use ReflectionClass;

class OptionFactory {
	use SingletonTrait;

	/**
	 * @var array<string, class-string>
	 */
	private const TYPES = array(
		'bool'                          => BoolOption::class,
		'int'                           => IntOption::class,
		'string'                        => StringOption::class,
		'select'                        => SelectOption::class,
		'language_select'               => LanguageSelectOption::class,
		'border'                        => BorderOption::class,
		'box_shadow'                    => BoxShadowOption::class,
		'directory_list'                => DirectoryListOption::class,
		'string_array'                  => StringArrayOption::class,
		'int_array'                     => IntArrayOption::class,
		'event_config_array'            => EventConfigArrayOption::class,
		'custom_field_rule_array'       => CustomFieldRuleArrayOption::class,
		'taxonomy_exclusion_rule_array' => TaxonomyExclusionRuleArrayOption::class,
		'priority_groups_array'         => PriorityGroupsArrayOption::class,
		'datetime'                      => DateTimeOption::class,
		'html'                          => HtmlOption::class,
	);

	/**
	 * Option types added at run time by register(), keyed by type name. Kept on the instance,
	 * so OptionFactory::resetInstance() forgets every registration.
	 *
	 * @var array<string, class-string<Option>>
	 */
	private array $registered = array();

	/**
	 * Creates an option of a built-in type (TYPES) or of a type added by register().
	 *
	 * @param string $type
	 * @param mixed  ...$args
	 *
	 * @return Option
	 * @throws InvalidArgumentException When the type is neither built in nor registered.
	 */
	public function create( string $type, ...$args ): Option {
		if ( isset(self::TYPES[ $type ]) ) {
			$class = self::TYPES[ $type ];
		} elseif ( isset($this->registered[ $type ]) ) {
			$class = $this->registered[ $type ];
		} else {
			throw new InvalidArgumentException("Invalid option type: $type"); // phpcs:ignore
		}

		/**
		* Unfortunately there is no better way for now to intelliJ to recognize
		* type hints based on the return value.
		* A proper solution would be: https://phpstan.org/r/a01e1e49-6f05-43a8-aac7-aded770cd88a
		* But in that case OptionFactory::instance()->create("className")->attr type hint is not working
		* Maybe in the future of IntelliJ?
		*/
		return new $class(...$args);
	}

	/**
	 * Adds an option type at run time, so an OptionData class can name it in its OPTIONS.
	 *
	 * The class name is compared and stored without a leading backslash and in its declared
	 * spelling, so registering the same class again is a no-op however it is written.
	 *
	 * @param string $type  The type name an OptionData's OPTIONS entry uses.
	 * @param string $class A class implementing both Option and JsonSerializable (the ORM
	 *                      serialises every option when saving), e.g. one extending AbstractOption.
	 *
	 * @throws InvalidArgumentException When $type is built in, when $class does not exist or does
	 *                                  not implement Option and JsonSerializable, when $class cannot
	 *                                  be instantiated (abstract, or a constructor that is not public),
	 *                                  or when $type is already registered to another class.
	 */
	public function register( string $type, string $class ): void {
		$class = ltrim($class, '\\');

		if ( isset(self::TYPES[ $type ]) ) {
			throw new InvalidArgumentException("Option type $type is built in"); // phpcs:ignore
		}

		if (
			!class_exists($class) ||
			!is_subclass_of($class, Option::class) ||
			!is_subclass_of($class, JsonSerializable::class)
		) {
			throw new InvalidArgumentException("$class must exist and implement Option and JsonSerializable"); // phpcs:ignore
		}

		// class_exists() is true for an abstract class, which create() could not build.
		$reflection = new ReflectionClass($class);
		if ( !$reflection->isInstantiable() ) {
			throw new InvalidArgumentException("$class cannot be instantiated"); // phpcs:ignore
		}

		$class = $reflection->getName();

		if ( isset($this->registered[ $type ]) ) {
			$existing = $this->registered[ $type ];
			if ( $existing === $class ) {
				return;
			}
			throw new InvalidArgumentException("Option type $type is already registered to $existing"); // phpcs:ignore
		}

		$this->registered[ $type ] = $class;
	}
}
