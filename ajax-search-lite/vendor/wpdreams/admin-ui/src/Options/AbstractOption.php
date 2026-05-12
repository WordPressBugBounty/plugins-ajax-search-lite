<?php

namespace WPDRMS\AdminUI\Options;

use JsonSerializable;
use WPDRMS\PluginCore\Traits\JsonSerializablePublicProperties;
use WPDRMS\Utils\ArrayUtils;

/**
 * @template T of Array<string, mixed>
 */
abstract class AbstractOption implements Option, JsonSerializable {
	use JsonSerializablePublicProperties;

	/**
	 * @var T
	 */
	protected array $defaults = array();

	/**
	 * The arguments from the constructor, merged and corrected with the defaults
	 *
	 * @var T
	 */
	protected array $args = array();

	/**
	 * @param Array<string, mixed> $args
	 */
	public function __construct( array $args ) {
		$this->args = ArrayUtils::arrayMergeRecursiveDistinct($this->defaults, $args);
	}

	public function value(): array {
		return $this->publicProperties();
	}
}
