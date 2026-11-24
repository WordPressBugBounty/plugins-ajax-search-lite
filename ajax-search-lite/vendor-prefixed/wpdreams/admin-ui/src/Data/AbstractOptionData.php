<?php

namespace AjaxSearchLite\Vendor\WPDRMS\AdminUI\Data;

use Exception;
use JsonSerializable;
use TypeError;
use AjaxSearchLite\Vendor\WPDRMS\AdminUI\OptionFactory;
use AjaxSearchLite\Vendor\WPDRMS\AdminUI\Options\Option;
use AjaxSearchLite\Vendor\WPDRMS\Utils\ArrayUtils;

/**
 * Handles option generation on the fly from DB stored values.
 *
 * This class does not communicate options to the DB, it is only a container.
 * For auto-persisted site-wide option groups use AbstractOptionDataSiteOption.
 *
 * @see .phpstorm.meta.php to specify Option types for get() method
 */
abstract class AbstractOptionData implements OptionData, JsonSerializable {
	/**
	 * All options must be also defined as public properties for type hinting and quick access
	 */
	// public BorderOption $sample_option_name1;
	// public ShadowOption $sample_option_name2;
	protected const OPTIONS = array(
		'sample_option_name1' => array(
			'type'         => 'border', // Factory type, defined in \WPDRMS\AdminUI\OptionFactory

			/**
			 * Default arguments passed to the factory constructor
			 */
			'default_args' => array(
				'width' => 2,
				'color' => 'blue',
			),
		),
		'sample_option_name2' => array(
			'type' => 'shadow',
		),
	);


	/**
	 * @var Array<string, string|Array<mixed>>
	 */
	protected array $args;

	/**
	 * @var Array<string, Option>
	 */
	protected array $options;


	/**
	 * @param array<string, mixed> $data
	 * @throws Exception
	 */
	public function __construct( array $data = array() ) {
		$this->setArgs($data);

		// This will initialize all options and populates the class properties
		$this->getAll();
	}

	/** @param array<string, mixed> $args */
	public function setArgs( array $args, $merge = true ): self {
		$this->args    = $merge ? array_merge($this->args ?? array(), $args) : $args;
		$this->options = array();
		return $this;
	}

	/**
	 * One stored option element as the args array every option constructor takes.
	 *
	 * Only the stored option as a whole is checked when it is loaded; an element can be anything a restored or
	 * hand-edited backup, `wp site option update`, a migration or a filter left there (wpdreams/ajax-search-pro#523):
	 * - an array: as it is;
	 * - a JSON string of an object or a list: decoded to an array (never a stdClass, which no option can take);
	 * - an object: its public properties.
	 *
	 * A bare value (a scalar, a string that is not JSON, JSON of a scalar) is NOT read as the option's value: it
	 * throws a TypeError, as it always did. Consumers depend on that to fail closed: site-core treats a setting
	 * stored flat as an unreadable row, so its secrets stay empty and its routes answer 503, instead of a stray
	 * string becoming a live bearer token (owner's decision, 2026-11-09). The message never carries the value.
	 *
	 * @param string $option_name
	 * @param mixed  $stored
	 * @return array<array-key, mixed>
	 * @throws TypeError When the element is a bare value.
	 */
	private static function storedArgs( string $option_name, $stored ): array {
		if ( is_array($stored) ) {
			return $stored;
		}
		if ( is_object($stored) ) {
			return get_object_vars($stored);
		}
		if ( is_string($stored) ) {
			$decoded = json_decode($stored, true);
			if ( is_array($decoded) ) {
				return $decoded;
			}
		}
		throw new TypeError(
			sprintf(
				'The stored option "%s" is a bare %s, not its arguments; it cannot be read.',
				$option_name, // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				gettype($stored) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			)
		);
	}

	/**
	 * @param string $option_name
	 * @return Option
	 * @throws Exception
	 */
	public function get( string $option_name ): Option {
		if ( isset($this->options[ $option_name ]) ) {
			return $this->options[ $option_name ];
		}

		if ( !isset(static::OPTIONS[ $option_name ]) ) {
			throw new Exception('Option key invalid!');
		}

		if ( isset($this->args[ $option_name ]) ) {
			$args = self::storedArgs($option_name, $this->args[ $option_name ]);
			if ( isset(static::OPTIONS[ $option_name ]['default_args']) ) {
				// Only fill in default_args keys that are absent from stored args.
				// Existing stored keys win entirely — this prevents numeric arrays
				// (e.g. string_array 'value') from merging with default values.
				$args = array_merge(
					static::OPTIONS[ $option_name ]['default_args'],
					$args
				);
			}
		} elseif ( isset(static::OPTIONS[ $option_name ]['default_args']) ) {
			$args = static::OPTIONS[ $option_name ]['default_args'];
		} else {
			$args = array();
		}

		$this->options[ $option_name ] = OptionFactory::instance()->create(
			static::OPTIONS[ $option_name ]['type'],
			$args
		);

		if ( !property_exists($this, $option_name) ) {
			// phpcs:ignore
			throw new Exception(
				"Property '$option_name' should explicitly exist in " . get_class($this) . '! Please define it!' // phpcs:ignore
			);
		}

		unset($this->{$option_name});
		$this->{$option_name} = $this->options[ $option_name ];

		return $this->options[ $option_name ];
	}

	/**
	 * @return Option[]
	 * @throws Exception
	 */
	public function getAll(): array {
		foreach ( static::OPTIONS as $key => $option ) {
			$this->get( $key );
		}
		return $this->options;
	}

	public function getDefault( string $option_name ): Option {
		if ( !isset(static::OPTIONS[ $option_name ]) ) {
			throw new Exception('Option key invalid!');
		}

		if ( isset(static::OPTIONS[ $option_name ]['default_args']) ) {
			$args = static::OPTIONS[ $option_name ]['default_args'];
		} else {
			$args = array();
		}

		return OptionFactory::instance()->create(
			static::OPTIONS[ $option_name ]['type'],
			$args
		);
	}

	/**
	 * @return Option[]
	 * @throws Exception
	 */
	public function getDefaults(): array {
		$options = array();
		foreach ( static::OPTIONS as $key => $option ) {
			$options[] = $this->getDefault( $key );
		}
		return $options;
	}


	public function jsonSerialize(): array {
		try {
			return array_map(
				function ( Option $o ) {
					return $o->jsonSerialize();
				},
				$this->getAll()
			);
		} catch ( Exception $e ) {
			return array();
		}
	}

	public function toJson(): string {
		try {
			$res = wp_json_encode($this->getAll());
		} catch ( Exception $e ) {
			return '{}';
		}
		return $res === false ? '{}' : $res;
	}
}
