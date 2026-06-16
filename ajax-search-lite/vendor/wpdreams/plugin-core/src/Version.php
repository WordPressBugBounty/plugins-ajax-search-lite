<?php

namespace WPDRMS\PluginCore;

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Reports the version of the *loaded* copy of plugin-core. Because this class is autoloaded by its
 * fully-qualified name, consumers reading {@see Version::get()} always get whichever copy actually
 * won the autoloader on this request — which is how the version guard detects a shadowing mismatch.
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
