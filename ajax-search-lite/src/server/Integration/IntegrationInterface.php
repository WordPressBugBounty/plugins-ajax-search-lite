<?php

namespace WPDRMS\ASL\Integration;
\defined( 'ABSPATH' ) || exit; \defined( 'ASL_SCOPED' ) || ! \defined( 'ASL_PATH' ) || require_once ASL_PATH . 'vendor-prefixed/autoload.php';

use AjaxSearchLite\Vendor\WPDRMS\PluginCore\Interfaces\Registrable;

if ( !defined('ABSPATH') ) {
	die('-1');
}

/**
 * A fix or a bridge for another plugin or theme, registered by the Manager outside ajax requests
 * (Factory 'Integration')
 */
interface IntegrationInterface extends Registrable {}
