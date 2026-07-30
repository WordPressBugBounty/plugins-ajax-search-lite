<?php

namespace WPDRMS\AdminUI\Routes;

use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WPDRMS\PluginCore\Rest\AbstractRest;

/**
 * Lists every registered post type for the admin UI post-type selector.
 *
 * Unlike the built-in `/wp/v2/types` endpoint, this is backed by
 * `get_post_types()`, so post types registered with `show_in_rest => false`
 * are included as well.
 */
class PostTypesRoute extends AbstractRest {
	public function registerRoutes( string $route_namespace = self::ROUTE_NAMESPACE ): void {
		$this->route_namespace = $this->sanitizeNamespace( $route_namespace );
		register_rest_route(
			$this->route_namespace,
			'options/post-types/get',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getPostTypes' ),
				'permission_callback' => array( $this, 'allowOnlyAdmins' ),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function getPostTypes( WP_REST_Request $request ) {
		try {
			$result = array();
			foreach ( get_post_types( array(), 'objects' ) as $slug => $object ) {
				if ( isset( $object->labels->name ) && $object->labels->name !== '' ) {
					$name = $object->labels->name;
				} elseif ( ! empty( $object->label ) ) {
					$name = $object->label;
				} else {
					$name = $slug;
				}

				$result[] = array(
					'slug' => $slug,
					'name' => (string) $name,
				);
			}

			/**
			 * Filters the post types returned to the admin UI post-type selector.
			 *
			 * @param array<int,array{slug:string,name:string}> $result Normalized post type list.
			 */
			$result = apply_filters( $this->vendorToken() . '_admin_ui_post_types', $result );

			return new WP_REST_Response( $result, 200 );
		} catch ( Exception $e ) {
			return new WP_Error( 'admin_ui_post_types', $e->getMessage() );
		}
	}
}
