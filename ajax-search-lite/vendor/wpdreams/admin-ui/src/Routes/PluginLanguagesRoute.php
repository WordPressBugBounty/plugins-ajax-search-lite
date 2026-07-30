<?php

namespace WPDRMS\AdminUI\Routes;

use WP_REST_Response;
use WPDRMS\PluginCore\Rest\AbstractRest;
use WPDRMS\Utils\Languages;

class PluginLanguagesRoute extends AbstractRest {
	public function registerRoutes( string $route_namespace = self::ROUTE_NAMESPACE ): void {
		$this->route_namespace = $this->sanitizeNamespace( $route_namespace );
		register_rest_route(
			$this->route_namespace,
			'options/languages/get',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getLanguages' ),
				'permission_callback' => array( $this, 'allowOnlyAdmins' ),
			)
		);
	}

	/** @return WP_REST_Response */
	public function getLanguages(): WP_REST_Response {
		$langs = Languages::getPluginLanguages();
		$items = array();
		foreach ( $langs as $code => $label ) {
			$items[] = array(
				'value' => $code,
				'label' => $label,
			);
		}
		return new WP_REST_Response( $items, 200 );
	}
}
