<?php

namespace WPDRMS\ASL\Cache;

use Exception;

trait CacheCommon {
	/**
	 * @param string $key
	 * @throws Exception
	 */
	protected function checkKey( string $key ): void {
		if ( strlen($key) > 14 ) {
			throw new Exception('Cache key too long');
		}
		if ( $key === '' ) {
			throw new Exception('Cache key is empty');
		}
	}
}
