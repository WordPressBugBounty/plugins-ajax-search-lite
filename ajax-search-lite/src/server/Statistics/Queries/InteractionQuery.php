<?php

namespace WPDRMS\ASL\Statistics\Queries;

use WPDRMS\PluginCore\Traits\SingletonTrait;

class InteractionQuery {
	use SingletonTrait;

	public function getLatestRInteractions( array $args = array() ): array {
		$results = array(
			(object) array(
				'id'               => 1,
				'result_id'        => 5,
				'search_id'        => 12,
				'result_type'      => 1,
				'phrase'           => 'wordpress plugin',
				'search_type'      => 'live',
				'date'             => date( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ),
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
				'result_id'        => 8,
				'search_id'        => 10,
				'result_type'      => 2,
				'phrase'           => 'tutorial',
				'search_type'      => 'live',
				'date'             => date( 'Y-m-d H:i:s', strtotime( '-5 hours' ) ),
				'user_name'        => null,
				'user_id'          => 0,
				'asp_id'           => 2,
				'page'             => 1,
				'referer'          => '/blog/',
				'interactions'     => 1,
				'device_type'      => 'mobile',
				'title'            => '[taxonomy: category] Tutorials',
				'url'              => '#',
				'result_type_name' => 'Taxonomy term',
			),
			(object) array(
				'id'               => 3,
				'result_id'        => 21,
				'search_id'        => 8,
				'result_type'      => 1,
				'phrase'           => 'woocommerce',
				'search_type'      => 'live',
				'date'             => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
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
			(object) array(
				'id'               => 4,
				'result_id'        => 12,
				'search_id'        => 11,
				'result_type'      => 1,
				'phrase'           => 'installation guide',
				'search_type'      => 'live',
				'date'             => date( 'Y-m-d H:i:s', strtotime( '-2 days' ) ),
				'user_name'        => 'admin',
				'user_id'          => 1,
				'asp_id'           => 1,
				'page'             => 1,
				'referer'          => '/',
				'interactions'     => 1,
				'device_type'      => 'tablet',
				'title'            => 'Plugin Installation Guide',
				'url'              => '#',
				'result_type_name' => 'Post Type',
			),
			(object) array(
				'id'               => 5,
				'result_id'        => 3,
				'search_id'        => 9,
				'result_type'      => 1,
				'phrase'           => 'configuration',
				'search_type'      => 'live',
				'date'             => date( 'Y-m-d H:i:s', strtotime( '-3 days' ) ),
				'user_name'        => null,
				'user_id'          => 0,
				'asp_id'           => 1,
				'page'             => 1,
				'referer'          => '/documentation/',
				'interactions'     => 1,
				'device_type'      => 'desktop',
				'title'            => 'Advanced Search Configuration',
				'url'              => '#',
				'result_type_name' => 'Post Type',
			),
		);

		return array(
			'results' => $results,
			'total'   => count( $results ),
		);
	}
}
