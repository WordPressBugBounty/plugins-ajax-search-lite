<?php

namespace WPDRMS\ASL\Statistics\Queries;

use WPDRMS\PluginCore\Traits\SingletonTrait;

class ResultQuery {

	use SingletonTrait;

	public function getPopularResults( array $args = array() ): array {
		$results = array(
			(object) array(
				'result_id'                        => 5,
				'result_type'                      => 1,
				'title'                            => 'Getting Started with WordPress',
				'url'                              => '#',
				'result_type_name'                 => 'Post Type',
				'views'                            => 248,
				'interactions'                     => 91,
				'views_prev'                       => 201,
				'interactions_prev'                => 74,
				'views_prev_change_percent'        => 23.38,
				'interactions_prev_change_percent' => 22.97,
			),
			(object) array(
				'result_id'                        => 12,
				'result_type'                      => 1,
				'title'                            => 'Plugin Installation Guide',
				'url'                              => '#',
				'result_type_name'                 => 'Post Type',
				'views'                            => 187,
				'interactions'                     => 62,
				'views_prev'                       => 143,
				'interactions_prev'                => 55,
				'views_prev_change_percent'        => 30.77,
				'interactions_prev_change_percent' => 12.73,
			),
			(object) array(
				'result_id'                        => 8,
				'result_type'                      => 2,
				'title'                            => '[taxonomy: category] Tutorials',
				'url'                              => '#',
				'result_type_name'                 => 'Taxonomy term',
				'views'                            => 143,
				'interactions'                     => 38,
				'views_prev'                       => 167,
				'interactions_prev'                => 49,
				'views_prev_change_percent'        => -14.37,
				'interactions_prev_change_percent' => -22.45,
			),
			(object) array(
				'result_id'                        => 3,
				'result_type'                      => 1,
				'title'                            => 'Advanced Search Configuration',
				'url'                              => '#',
				'result_type_name'                 => 'Post Type',
				'views'                            => 96,
				'interactions'                     => 21,
				'views_prev'                       => 88,
				'interactions_prev'                => 17,
				'views_prev_change_percent'        => 9.09,
				'interactions_prev_change_percent' => 23.53,
			),
			(object) array(
				'result_id'                        => 21,
				'result_type'                      => 1,
				'title'                            => 'WooCommerce Integration',
				'url'                              => '#',
				'result_type_name'                 => 'Post Type',
				'views'                            => 74,
				'interactions'                     => 18,
				'views_prev'                       => 61,
				'interactions_prev'                => 14,
				'views_prev_change_percent'        => 21.31,
				'interactions_prev_change_percent' => 28.57,
			),
		);

		return array(
			'results' => $results,
			'total'   => count( $results ),
			'compare' => null,
		);
	}

	public function getLatestResults( array $args = array() ): array {
		$results = array(
			(object) array(
				'id'               => 1,
				'result_id'        => 5,
				'search_id'        => 12,
				'result_type'      => 1,
				'phrase'           => 'wordpress plugin',
				'search_type'      => 'live',
				'date'             => date( 'Y-m-d H:i:s', strtotime( '-2 hours' ) ),
				'user_name'        => null,
				'user_id'          => 0,
				'asp_id'           => 1,
				'page'             => 1,
				'referer'          => '/shop/',
				'interactions'     => 1,
				'device_type'      => 'desktop',
				'title'            => 'Getting Started with WordPress',
				'url'              => '#',
				'result_type_name' => 'Post Type',
			),
			(object) array(
				'id'               => 2,
				'result_id'        => 12,
				'search_id'        => 11,
				'result_type'      => 1,
				'phrase'           => 'installation',
				'search_type'      => 'live',
				'date'             => date( 'Y-m-d H:i:s', strtotime( '-4 hours' ) ),
				'user_name'        => 'admin',
				'user_id'          => 1,
				'asp_id'           => 1,
				'page'             => 1,
				'referer'          => '/',
				'interactions'     => 0,
				'device_type'      => 'mobile',
				'title'            => 'Plugin Installation Guide',
				'url'              => '#',
				'result_type_name' => 'Post Type',
			),
			(object) array(
				'id'               => 3,
				'result_id'        => 8,
				'search_id'        => 10,
				'result_type'      => 2,
				'phrase'           => 'tutorial',
				'search_type'      => 'live',
				'date'             => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
				'user_name'        => null,
				'user_id'          => 0,
				'asp_id'           => 2,
				'page'             => 1,
				'referer'          => '/blog/',
				'interactions'     => 1,
				'device_type'      => 'desktop',
				'title'            => '[taxonomy: category] Tutorials',
				'url'              => '#',
				'result_type_name' => 'Taxonomy term',
			),
			(object) array(
				'id'               => 4,
				'result_id'        => 3,
				'search_id'        => 9,
				'result_type'      => 1,
				'phrase'           => 'search config',
				'search_type'      => 'live',
				'date'             => date( 'Y-m-d H:i:s', strtotime( '-2 days' ) ),
				'user_name'        => null,
				'user_id'          => 0,
				'asp_id'           => 1,
				'page'             => 1,
				'referer'          => '/documentation/',
				'interactions'     => 0,
				'device_type'      => 'tablet',
				'title'            => 'Advanced Search Configuration',
				'url'              => '#',
				'result_type_name' => 'Post Type',
			),
			(object) array(
				'id'               => 5,
				'result_id'        => 21,
				'search_id'        => 8,
				'result_type'      => 1,
				'phrase'           => 'woocommerce',
				'search_type'      => 'live',
				'date'             => date( 'Y-m-d H:i:s', strtotime( '-3 days' ) ),
				'user_name'        => null,
				'user_id'          => 0,
				'asp_id'           => 1,
				'page'             => 1,
				'referer'          => '/shop/',
				'interactions'     => 1,
				'device_type'      => 'desktop',
				'title'            => 'WooCommerce Integration',
				'url'              => '#',
				'result_type_name' => 'Post Type',
			),
		);

		return array(
			'results' => $results,
			'total'   => count( $results ),
		);
	}

	public function getResultsForSearch(): array {
		return array(
			(object) array(
				'id'               => 1,
				'result_id'        => 5,
				'result_type'      => 1,
				'title'            => 'Getting Started with WordPress',
				'url'              => '#',
				'result_type_name' => 'Post Type',
				'search_id'        => 1,
			),
			(object) array(
				'id'               => 2,
				'result_id'        => 12,
				'result_type'      => 1,
				'title'            => 'Plugin Installation Guide',
				'url'              => '#',
				'result_type_name' => 'Post Type',
				'search_id'        => 1,
			),
			(object) array(
				'id'               => 3,
				'result_id'        => 8,
				'result_type'      => 2,
				'title'            => '[taxonomy: category] Tutorials',
				'url'              => '#',
				'result_type_name' => 'Taxonomy term',
				'search_id'        => 1,
			),
		);
	}
}
