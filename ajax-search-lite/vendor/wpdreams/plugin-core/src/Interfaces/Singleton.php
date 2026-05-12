<?php

namespace WPDRMS\PluginCore\Interfaces;

interface Singleton {
	/**
	 * @return self
	 */
	public static function instance();
}
