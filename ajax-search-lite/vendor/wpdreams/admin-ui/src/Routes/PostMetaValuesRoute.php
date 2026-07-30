<?php

namespace WPDRMS\AdminUI\Routes;

use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WPDRMS\PluginCore\Rest\AbstractRest;

class PostMetaValuesRoute extends AbstractRest {
	public function registerRoutes( string $route_namespace = self::ROUTE_NAMESPACE ): void {
		$this->route_namespace = $this->sanitizeNamespace( $route_namespace );
		register_rest_route(
			$this->route_namespace,
			'options/post-meta-values/get',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getPostMetaValues' ),
				'permission_callback' => array( $this, 'allowOnlyAdmins' ),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function getPostMetaValues( WP_REST_Request $request ) {
		try {
			global $wpdb;
			$key = sanitize_text_field( $request->get_param( 'key' ) ?? '' );

			if ( $key === '' ) {
				return new WP_REST_Response( array( 'values' => array() ), 200 );
			}

			$search = sanitize_text_field( $request->get_param( 'search' ) ?? '' );
			$limit  = min( 50, max( 1, (int) ( $request->get_param( 'limit' ) ?? 20 ) ) );

			if ( $search !== '' ) {
				$like = '%' . $wpdb->esc_like( $search ) . '%';
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$values = $wpdb->get_col(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery
						"SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value NOT LIKE 'a:%%' AND meta_value NOT LIKE 'O:%%' AND meta_value NOT LIKE 's:%%' AND CHAR_LENGTH(meta_value) <= 100 AND meta_value LIKE %s ORDER BY meta_value ASC LIMIT %d",
						$key,
						$like,
						$limit
					)
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$values = $wpdb->get_col(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery
						"SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value NOT LIKE 'a:%%' AND meta_value NOT LIKE 'O:%%' AND meta_value NOT LIKE 's:%%' AND CHAR_LENGTH(meta_value) <= 100 ORDER BY meta_value ASC LIMIT %d",
						$key,
						$limit
					)
				);
			}

			return new WP_REST_Response(
				array( 'values' => $values ),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'admin_ui_post_meta_values', $e->getMessage() );
		}
	}
}
