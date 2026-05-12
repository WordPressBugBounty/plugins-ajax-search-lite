<div class="wdo" id="wdo"><div id="asp-root"></div></div>
<?php

use WPDRMS\Utils\Script;

$metadata = require_once ASL_PATH . 'build/css/wdo-global.asset.php';
wp_enqueue_style(
	'wpo-global',
	ASL_URL_NP . 'build/css/wdo-global.css',
	$metadata['dependencies'],
	$metadata['version'],
);



$metadata = require_once ASL_PATH . 'build/js/cache.asset.php';
wp_enqueue_script(
	'wpd-asp-cache',
	ASL_URL_NP . 'build/js/cache.js',
	$metadata['dependencies'],
	$metadata['version'],
	array( 'in_footer' =>true ),
);

$metadata = require_once ASL_PATH . '/src/server/Asset/global.asset.php';
Script::objectToInlineScript(
	'wpd-asp-cache',
	'ASL_BACKEND',
	$metadata['ASL_BACKEND'],
	'before',
	true,
);
