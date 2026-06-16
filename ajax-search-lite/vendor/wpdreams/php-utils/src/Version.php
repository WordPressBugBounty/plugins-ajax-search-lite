<?php

namespace WPDRMS\Utils;

/**
 * Reports the version of the *loaded* copy of php-utils (read from the package's version.php).
 * Autoloaded by FQCN, so consumers always see whichever copy won the autoloader this request —
 * which is how the shared-library version guard detects a shadowing mismatch.
 */
final class Version {
	public static function get(): string {
		static $version = null;
		if ( $version === null ) {
			$version = (string) require dirname( __DIR__ ) . '/version.php';
		}
		return $version;
	}
}
