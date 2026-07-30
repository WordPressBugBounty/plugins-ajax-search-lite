<?php

namespace WPDRMS\AdminUI\Routes;

use WP_REST_Request;
use WP_REST_Response;
use WPDRMS\PluginCore\Rest\AbstractRest;

class SitesRoute extends AbstractRest {
	public function registerRoutes( string $route_namespace = self::ROUTE_NAMESPACE ): void {
		$this->route_namespace = $this->sanitizeNamespace( $route_namespace );
		register_rest_route(
			$this->route_namespace,
			'options/sites/get',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getSites' ),
				'permission_callback' => array( $this, 'allowOnlyAdmins' ),
			)
		);
	}

	/** @return WP_REST_Response */
	public function getSites( WP_REST_Request $request ): WP_REST_Response {
		if ( ! is_multisite() ) {
			return new WP_REST_Response( array(), 200 );
		}
		/** @var \WP_Site[]|int $sites */
		$sites  = get_sites( array( 'number' => 200 ) );
		$result = array();
		if ( is_array( $sites ) ) {
			foreach ( $sites as $site ) {
				switch_to_blog( (int) $site->blog_id );
				$result[] = array(
					'id'   => (int) $site->blog_id,
					'name' => get_bloginfo( 'name' ) ?: "Blog #{$site->blog_id}",
					'url'  => get_bloginfo( 'url' ),
				);
				restore_current_blog();
			}
		}
		return new WP_REST_Response( $result, 200 );
	}
}
