<?php

namespace WPDRMS\PluginCore\Interfaces;

interface Registrable {
	public function register(): void;

	public function deregister(): void;
}
