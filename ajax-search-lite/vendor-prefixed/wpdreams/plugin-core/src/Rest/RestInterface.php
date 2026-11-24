<?php

namespace AjaxSearchLite\Vendor\WPDRMS\PluginCore\Rest;

use AjaxSearchLite\Vendor\WPDRMS\PluginCore\Interfaces\Singleton;

interface RestInterface extends Singleton {
	public function registerRoutes( string $route_namespace ): void;
}
