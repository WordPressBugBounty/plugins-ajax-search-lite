<?php

namespace WPDRMS\ASL\Maintenance;

use Exception;
use WP_Error;
use WP_REST_Response;
use WPDRMS\PluginCore\Rest\AbstractRest;

class MaintenanceRoute extends AbstractRest {

	public function registerRoutes( string $route_namespace ): void {
		register_rest_route(
			$route_namespace,
			'maintenance/reset',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'reset' ),
				'permission_callback' => array( $this, 'allowOnlyAdmins' ),
			)
		);

		register_rest_route(
			$route_namespace,
			'maintenance/wipe',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'wipe' ),
				'permission_callback' => array( $this, 'allowOnlyAdmins' ),
			)
		);
	}

	/** @return WP_Error|WP_REST_Response */
	public function reset() {
		try {
			wd_asl()->init->pluginReset();
			return new WP_REST_Response( array( 'success' => true ), 200 );
		} catch ( Exception $e ) {
			return new WP_Error( 'asl_maintenance_reset', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/** @return WP_Error|WP_REST_Response */
	public function wipe() {
		try {
			wd_asl()->init->pluginWipe();
			return new WP_REST_Response( array( 'success' => true ), 200 );
		} catch ( Exception $e ) {
			return new WP_Error( 'asl_maintenance_wipe', $e->getMessage(), array( 'status' => 500 ) );
		}
	}
}
