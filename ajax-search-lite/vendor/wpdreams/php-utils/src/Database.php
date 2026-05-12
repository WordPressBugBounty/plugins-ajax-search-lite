<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace WPDRMS\Utils;

class Database {
	/**
	 * Gets the responsiveness of the database connection in seconds
	 *
	 * @return float
	 */
	public static function getResponsiveness(): float {
		global $wpdb;
		$start = microtime(true);
		$wpdb->flush();
		$wpdb->get_var("SELECT RAND()");  // phpcs:ignore
		return microtime(true) - $start;  // e.g., if > 0.05-0.1s, consider slow
	}
}
