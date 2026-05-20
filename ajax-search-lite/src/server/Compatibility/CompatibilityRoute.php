<?php

namespace WPDRMS\ASL\Compatibility;

use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WPDRMS\ASL\Compatibility\ORM\CompatibilityOptions;
use WPDRMS\PluginCore\Rest\AbstractRest;

class CompatibilityRoute extends AbstractRest {

	public function registerRoutes( string $route_namespace ): void {
		register_rest_route(
			$route_namespace,
			'options/compatibility',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'getOptions' ),
					'permission_callback' => array( $this, 'allowOnlyAdmins' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'saveOptions' ),
					'permission_callback' => array( $this, 'allowOnlyAdmins' ),
				),
			)
		);

		register_rest_route(
			$route_namespace,
			'options/compatibility/reset',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'resetOptions' ),
				'permission_callback' => array( $this, 'allowOnlyAdmins' ),
			)
		);
	}

	/** @return WP_Error|WP_REST_Response */
	public function getOptions() {
		try {
			return new WP_REST_Response( CompatibilityOptions::instance(), 200 );
		} catch ( Exception $e ) {
			return new WP_Error( 'asl_compatibility_get', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function saveOptions( WP_REST_Request $request ) {
		try {
			$params = $request->get_json_params();
			$orm    = CompatibilityOptions::instance()->setArgs( $params )->save()->load();
			return new WP_REST_Response( $orm, 200 );
		} catch ( Exception $e ) {
			return new WP_Error( 'asl_compatibility_save', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/** @return WP_Error|WP_REST_Response */
	public function resetOptions() {
		try {
			$orm = CompatibilityOptions::instance()->saveDefaults()->load();
			return new WP_REST_Response( $orm, 200 );
		} catch ( Exception $e ) {
			return new WP_Error( 'asl_compatibility_reset', $e->getMessage(), array( 'status' => 500 ) );
		}
	}
}
