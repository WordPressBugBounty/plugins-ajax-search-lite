<?php

namespace WPDRMS\AdminUI\Routes;

use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WPDRMS\PluginCore\Rest\AbstractRest;

class PostsRoute extends AbstractRest {
	public function registerRoutes( string $route_namespace = self::ROUTE_NAMESPACE ): void {
		$this->route_namespace = $this->sanitizeNamespace( $route_namespace );
		register_rest_route(
			$this->route_namespace,
			'options/posts/search',
			array(
				'methods'             => 'GET',
				'callback'            => array(
					$this,
					'searchPosts',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);
		register_rest_route(
			$this->route_namespace,
			'options/posts/get',
			array(
				'methods'             => 'GET',
				'callback'            => array(
					$this,
					'getPostsByIds',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function searchPosts( WP_REST_Request $request ): WP_REST_Response {
		try {
			$args  = array(
				's'              => sanitize_text_field( $request->get_param( 'search' ) ?? '' ),
				'post_type'      => sanitize_text_field( $request->get_param( 'post_type' ) ?: 'any' ),
				'posts_per_page' => min( 50, max( 1, (int) ( $request->get_param( 'limit' ) ?? 20 ) ) ),
				'post_status'    => array( 'publish', 'draft', 'private' ),
			);
			$query = new WP_Query( $args );
			$posts = array();
			foreach ( $query->posts as $post ) {
				$posts[] = array(
					'id'        => $post->ID,
					'title'     => $post->post_title ?: '(no title)',
					'post_type' => $post->post_type,
					'date'      => $post->post_date,
				);
			}
			return new WP_REST_Response( $posts, 200 );
		} catch ( \Exception $e ) {
			return new WP_REST_Response(
				new WP_Error( 'posts_search', $e->getMessage() ),
				400
			);
		}
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function getPostsByIds( WP_REST_Request $request ): WP_REST_Response {
		try {
			$raw  = $request->get_param( 'ids' ) ?? '';
			$ids  = array_filter( array_map( 'intval', explode( ',', $raw ) ) );
			if ( empty( $ids ) ) {
				return new WP_REST_Response( array(), 200 );
			}
			$query = new WP_Query( array(
				'post__in'       => $ids,
				'post_type'      => 'any',
				'posts_per_page' => count( $ids ),
				'post_status'    => array( 'publish', 'draft', 'private', 'inherit' ),
				'orderby'        => 'post__in',
			) );
			$posts = array();
			foreach ( $query->posts as $post ) {
				$posts[] = array(
					'id'        => $post->ID,
					'title'     => $post->post_title ?: '(no title)',
					'post_type' => $post->post_type,
				);
			}
			return new WP_REST_Response( $posts, 200 );
		} catch ( \Exception $e ) {
			return new WP_REST_Response(
				new WP_Error( 'posts_get', $e->getMessage() ),
				400
			);
		}
	}
}
