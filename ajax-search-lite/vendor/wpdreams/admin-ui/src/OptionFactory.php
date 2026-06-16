<?php

namespace WPDRMS\AdminUI;

use WPDRMS\AdminUI\Options\BoolOption;
use WPDRMS\AdminUI\Options\BorderOption;
use WPDRMS\AdminUI\Options\BoxShadowOption;
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
	);

	/**
	 * @param string $type
	 * @param mixed  ...$args
	 *
	 * @return Option
	 * @throws InvalidArgumentException
	 */
	public function create( string $type, ...$args ): Option {
		if ( !isset(self::TYPES[ $type ]) ) {
			throw new InvalidArgumentException("Invalid option type: $type"); // phpcs:ignore
		}

		$class = self::TYPES[ $type ];

		/**
		* Unfortunately there is no better way for now to intelliJ to recognize
		* type hints based on the return value.
		* A proper solution would be: https://phpstan.org/r/a01e1e49-6f05-43a8-aac7-aded770cd88a
		* But in that case OptionFactory::instance()->create("className")->attr type hint is not working
		* Maybe in the future of IntelliJ?
		*/
		return new $class(...$args);
	}
}
