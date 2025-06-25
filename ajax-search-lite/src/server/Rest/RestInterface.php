<?php

namespace WPDRMS\ASL\Rest;

interface RestInterface {
	/**
	 * @return self
	 */
	public static function instance();

	public function registerRoutes(): void;
}
