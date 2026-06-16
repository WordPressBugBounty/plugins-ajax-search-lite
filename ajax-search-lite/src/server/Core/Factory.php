<?php

namespace WPDRMS\ASL\Core;

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

use WPDRMS\ASL\Analytics\AnalyticsRoute;
use WPDRMS\ASL\BlockEditor\ASLBlock;
use WPDRMS\ASL\Cache\CacheRoute;
use WPDRMS\ASL\Compatibility\CompatibilityRoute;
use WPDRMS\ASL\Maintenance\MaintenanceRoute;
use WPDRMS\ASL\Statistics\StatisticsRoute;
use WPDRMS\PluginCore\Traits\SingletonTrait;
use WPDRMS\ASL\Options\Routes\SearchOptionsRoute;
use WPDRMS\ASL\Options\Routes\TaxonomyTermsRoute;

/**
 * Returns all class instances for a given interface name
 *
 * @see .phpstorm.meta.php for corrected type hints
 */
class Factory {
	use SingletonTrait;

	const SUPPORTED_INTERFACES = array(
		'Rest'  => array(
			TaxonomyTermsRoute::class,
			SearchOptionsRoute::class,
			StatisticsRoute::class,
			CacheRoute::class,
			AnalyticsRoute::class,
			CompatibilityRoute::class,
			MaintenanceRoute::class,
		),
		'Block' => array(
			ASLBlock::class,
		),
	);

	/**
	 * Get all the objects array for a given interface
	 *
	 * @param key-of<self::SUPPORTED_INTERFACES> $interface_name
	 * @param mixed[]                            $args
	 */
	public function get( string $interface_name, ?array $args = null ): array {
		if ( !isset(self::SUPPORTED_INTERFACES[ $interface_name ]) ) {
			return array();
		}
		return self::instantiate( self::SUPPORTED_INTERFACES[ $interface_name ], $args );
	}

	/**
	 * Instantiates a list of classes, skipping any that no longer exist.
	 *
	 * A listed class may be missing if it was removed in a newer version while
	 * an older Factory (still referencing it) is in memory during the plugin
	 * file-swap on an update. Skipping it degrades gracefully (one hook/route
	 * not registered for that request) instead of fataling the whole admin /
	 * REST request in that narrow window.
	 *
	 * @param string[]     $classes
	 * @param mixed[]|null $args
	 * @return object[]
	 */
	public static function instantiate( array $classes, ?array $args = null ): array {
		$objects = array();
		foreach ( $classes as $class_name ) {
			if ( !class_exists($class_name) ) {
				if ( defined('WP_DEBUG') && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log(sprintf('Ajax Search Lite: skipped missing class "%s" during Factory instantiation.', $class_name));
				}
				continue;
			}
			if ( method_exists($class_name, 'instance') ) {
				$objects[] = is_array($args) ? $class_name::instance(...$args) : $class_name::instance();
			} else {
				$objects[] = is_array($args) ? new $class_name(...$args) : new $class_name(); // @phpstan-ignore-line
			}
		}
		return $objects;
	}
}
