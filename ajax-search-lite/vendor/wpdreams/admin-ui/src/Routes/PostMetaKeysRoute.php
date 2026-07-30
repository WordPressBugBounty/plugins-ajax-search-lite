<?php

namespace WPDRMS\AdminUI\Routes;

use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WPDRMS\PluginCore\Rest\AbstractRest;

class PostMetaKeysRoute extends AbstractRest {
	public function registerRoutes( string $route_namespace = self::ROUTE_NAMESPACE ): void {
		$this->route_namespace = $this->sanitizeNamespace( $route_namespace );
		register_rest_route(
			$this->route_namespace,
			'options/post-meta-keys/get',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getPostMetaKeys' ),
				'permission_callback' => array( $this, 'allowOnlyAdmins' ),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function getPostMetaKeys( WP_REST_Request $request ) {
		try {
			global $wpdb;
			$search = sanitize_text_field( $request->get_param( 'search' ) ?? '' );
			$limit  = min( 50, max( 1, (int) ( $request->get_param( 'limit' ) ?? 20 ) ) );

			$like             = $wpdb->esc_like( $search ) . '%';
			$exclude_internal = ( $search === '' || $search[0] !== '_' );

			if ( $exclude_internal ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$keys = $wpdb->get_col(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery
						"SELECT DISTINCT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE %s AND meta_key NOT LIKE '\\_%%' ORDER BY meta_key ASC LIMIT %d",
						$like,
						$limit
					)
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$keys = $wpdb->get_col(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						"SELECT DISTINCT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE %s ORDER BY meta_key ASC LIMIT %d",
						$like,
						$limit
					)
				);
			}

			$acf_groups = array();
			if ( function_exists( 'acf_get_field_groups' ) ) {
				foreach ( acf_get_field_groups() as $group ) {
					$fields = array();
					foreach ( acf_get_fields( $group ) as $field ) {
						$fields[] = array(
							'key'   => $field['name'],
							'label' => $field['label'],
							'type'  => $field['type'],
						);
					}
					$acf_groups[] = array(
						'title'  => $group['title'],
						'fields' => $fields,
					);
				}
			}

			return new WP_REST_Response(
				array(
					'keys'       => $keys,
					'acf_groups' => $acf_groups,
				),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'admin_ui_post_meta_keys', $e->getMessage() );
		}
	}
}
