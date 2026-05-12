<?php

namespace WPDRMS\ASL\Cache;

use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WPDRMS\ASL\Cache\ORM\CacheOptions;
use WPDRMS\ASL\Cache\Queries\SearchStatisticsQuery;
use WPDRMS\PluginCore\Rest\AbstractRest;

class CacheRoute extends AbstractRest {
	public function registerRoutes( string $route_namespace ): void {
		register_rest_route(
			$route_namespace,
			'options/cache',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array(
						$this,
						'getOptions',
					),
					'permission_callback' => array(
						$this,
						'allowOnlyAdmins',
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array(
						$this,
						'saveOptions',
					),
					'permission_callback' => array(
						$this,
						'allowOnlyAdmins',
					),
				),
			),
		);

		register_rest_route(
			$route_namespace,
			'options/cache/reset',
			array(
				'methods'             => 'POST',
				'callback'            => array(
					$this,
					'resetOptions',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);

		register_rest_route(
			$route_namespace,
			'results_cache/realtime',
			array(
				'methods'             => 'GET',
				'callback'            => array(
					$this,
					'getStatistics',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);

		register_rest_route(
			$route_namespace,
			'results_cache/keyset',
			array(
				'methods'             => 'GET',
				'callback'            => array(
					$this,
					'keySet',
				),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$route_namespace,
			'cache/purge',
			array(
				'methods'             => 'POST',
				'callback'            => array(
					$this,
					'purge',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);
	}

	/**
	 * @return WP_Error|WP_REST_Response
	 */
	public function getOptions() {
		try {
			return new WP_REST_Response(
				CacheOptions::instance(),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('cache_options_get', $e->getMessage());
		}
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function saveOptions( WP_REST_Request $request ) {
		try {
			$params  = $request->get_json_params();
			$options = CacheOptions::instance();
			$options->setArgs($params)
				->save()
				->load(); // Reload data from DB just to be sure it is stored
			ResultsCacheService::instance()->purge();
			ImageCacheService::instance()->purge();
			return new WP_REST_Response(
				$options,
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('cache_options_save', $e->getMessage());
		}
	}

	/**
	 * @return WP_Error|WP_REST_Response
	 */
	public function resetOptions() {
		try {
			$options = CacheOptions::instance();
			$options->saveDefaults()->load();
			return new WP_REST_Response(
				$options,
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('cache_options_reset', $e->getMessage());
		}
	}

	/**
	 * @return WP_Error|WP_REST_Response
	 */
	public function getStatistics() {
		try {
			return new WP_REST_Response(
				array_merge(
					ResultsCacheService::instance()->search_statistics_query->getRealTimeSearchStatistics(),
					array(
						'cached' => ResultsCacheService::instance()->count(),
					)
				),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('cache_statistics_get', $e->getMessage());
		}
	}

	/**
	 * @return WP_Error|WP_REST_Response
	 */
	public function keySet() {
		try {
			return new WP_REST_Response(
				ResultsCacheService::instance()->keySet(),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('cache_keyset', $e->getMessage());
		}
	}

	/**
	 * @return WP_Error|WP_REST_Response
	 */
	public function purge() {
		try {
			$count  = ResultsCacheService::instance()->purge();
			$count += ImageCacheService::instance()->purge();
			return new WP_REST_Response(
				array(
					'purged' => $count,
				),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('cache_purge', $e->getMessage());
		}
	}
}
