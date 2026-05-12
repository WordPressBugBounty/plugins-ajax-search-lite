<?php

namespace WPDRMS\PluginCore\Rest;

use WPDRMS\PluginCore\Interfaces\Singleton;

interface RestInterface extends Singleton {
	public function registerRoutes( string $route_namespace ): void;
}
