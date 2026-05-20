<?php

namespace WPDRMS\ASL\Statistics;

use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WPDRMS\PluginCore\Rest\AbstractRest;
use WPDRMS\ASL\Statistics\ORM\Search;
use WPDRMS\ASL\Statistics\ORM\StatisticsOptions;

class StatisticsRoute extends AbstractRest {
	public function registerRoutes( string $route_namespace ): void {
		register_rest_route(
			$route_namespace,
			'options/statistics',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array(
						$this,
						'getStatisticsOptions',
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
						'saveStatisticsOptions',
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
			'options/statistics/reset',
			array(
				'methods'             => 'POST',
				'callback'            => array(
					$this,
					'resetStatisticsOptions',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);

		register_rest_route(
			$route_namespace,
			'statistics/realtime',
			array(
				'methods'             => 'GET',
				'callback'            => array(
					$this,
					'getRealtimeStats',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);

		register_rest_route(
			$route_namespace,
			'statistics/searches/add',
			array(
				'methods'             => 'POST',
				'callback'            => array(
					$this,
					'addStatisticsSearches',
				),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$route_namespace,
			'statistics/searches/latest',
			array(
				'methods'             => 'GET',
				'callback'            => array(
					$this,
					'getStatisticsLatestSearches',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);

		register_rest_route(
			$route_namespace,
			'statistics/searches/delete',
			array(
				'methods'             => 'POST',
				'callback'            => array(
					$this,
					'deleteStatisticsLatestSearch',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);

		register_rest_route(
			$route_namespace,
			'statistics/searches/volume',
			array(
				'methods'             => 'GET',
				'callback'            => array(
					$this,
					'getStatisticsSearchesVolume',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);

		register_rest_route(
			$route_namespace,
			'statistics/searches/popular',
			array(
				'methods'             => 'GET',
				'callback'            => array(
					$this,
					'getStatisticsPopularSearches',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);

		register_rest_route(
			$route_namespace,
			'statistics/searches/volume/csv',
			array(
				'methods'             => 'GET',
				'callback'            => array(
					$this,
					'exportStatisticsSearchesVolumeToCSV',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);

		register_rest_route(
			$route_namespace,
			'statistics/searches/popular/csv',
			array(
				'methods'             => 'GET',
				'callback'            => array(
					$this,
					'exportStatisticsPopularSearchesToCSV',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);

		register_rest_route(
			$route_namespace,
			'statistics/searches/latest/csv',
			array(
				'methods'             => 'GET',
				'callback'            => array(
					$this,
					'exportStatisticsLatestSearchesToCSV',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);

		register_rest_route(
			$route_namespace,
			'statistics/results',
			array(
				'methods'             => 'GET',
				'callback'            => array(
					$this,
					'getStatisticsResults',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);

		register_rest_route(
			$route_namespace,
			'statistics/results/popular',
			array(
				'methods'             => 'GET',
				'callback'            => array(
					$this,
					'getStatisticsPopularResults',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);

		register_rest_route(
			$route_namespace,
			'statistics/results/latest',
			array(
				'methods'             => 'GET',
				'callback'            => array(
					$this,
					'getStatisticsLatestResults',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);

		register_rest_route(
			$route_namespace,
			'statistics/interactions/latest',
			array(
				'methods'             => 'GET',
				'callback'            => array(
					$this,
					'getStatisticsInteractionsLatest',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);

		register_rest_route(
			$route_namespace,
			'statistics/reset',
			array(
				'methods'             => 'POST',
				'callback'            => array(
					$this,
					'resetStatistics',
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
	 * @return WP_Error|WP_REST_Response
	 */
	public function addStatisticsSearches( WP_REST_Request $request ) {
		try {
			$options = StatisticsOptions::instance();
			if (
				!$options->status->value
			) {
				throw new Exception('Search statistics is disabled.');
			}
			$params = $request->get_json_params();
			StatisticsService::instance()->registerCachedSearchesQueries( $params );
			return new WP_REST_Response(
				null,
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('searches_add', $e->getMessage());
		}
	}

	public function getStatisticsOptions() {
		try {
			return new WP_REST_Response(
				StatisticsOptions::instance(),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('statistics_options_get', $e->getMessage());
		}
	}

	public function getStatisticsResults( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response(
				StatisticsService::instance()->result_query->getResultsForSearch(),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('statistics_get_results', $e->getMessage());
		}
	}

	public function getStatisticsPopularResults( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response(
				StatisticsService::instance()->result_query->getPopularResults($request->get_params()),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('statistics_popular_results', $e->getMessage());
		}
	}

	public function getStatisticsLatestResults( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response(
				StatisticsService::instance()->result_query->getLatestResults($request->get_params()),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('statistics_latest_results', $e->getMessage());
		}
	}

	public function getStatisticsInteractionsLatest( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response(
				StatisticsService::instance()->interaction_query->getLatestRInteractions($request->get_params()),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('statistics_latest_interactions', $e->getMessage());
		}
	}

	public function getStatisticsSearchesVolume( WP_REST_Request $request ) {
		try {
			$params = $request->get_params();
			return new WP_REST_Response(
				StatisticsService::instance()->search_query->getSearchesVolume($params),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('statistics_daily_searches', $e->getMessage());
		}
	}

	public function getStatisticsPopularSearches( WP_REST_Request $request ) {
		try {
			$params              = $request->get_params();
			$params['variation'] = 'popular';
			return new WP_REST_Response(
				StatisticsService::instance()->search_query->getSearches($params),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('statistics_popular_searches', $e->getMessage());
		}
	}

	public function exportStatisticsSearchesVolumeToCSV( WP_REST_Request $request ) {
		try {
			$params = $request->get_params();
			StatisticsService::instance()->search_csv_exporter->getSearchesVolumeToCsv($params);
		} catch ( Exception $e ) {
			return new WP_Error('statistics_searches_volume_export_csv', $e->getMessage());
		}
	}

	public function exportStatisticsPopularSearchesToCSV( WP_REST_Request $request ) {
		try {
			$params              = $request->get_params();
			$params['variation'] = 'popular';
			StatisticsService::instance()->search_csv_exporter->getSearchesToCsv($params);
		} catch ( Exception $e ) {
			return new WP_Error('statistics_popular_searches_export_csv', $e->getMessage());
		}
	}

	public function exportStatisticsLatestSearchesToCSV( WP_REST_Request $request ) {
		try {
			$params              = $request->get_params();
			$params['variation'] = 'latest';
			StatisticsService::instance()->search_csv_exporter->getSearchesToCsv($params);
		} catch ( Exception $e ) {
			return new WP_Error('statistics_latest_searches_export_csv', $e->getMessage());
		}
	}

	public function getStatisticsLatestSearches( WP_REST_Request $request ) {
		try {
			$params              = $request->get_params();
			$params['variation'] = 'latest';
			return new WP_REST_Response(
				StatisticsService::instance()->search_query->getSearches($params),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('statistics_latest_searches', $e->getMessage());
		}
	}

	public function deleteStatisticsLatestSearch( WP_REST_Request $request ) {
		try {
			$ids = $request->get_param('ids') ?? null;
			$id  = $request->get_param('id') ?? null;

			if ( is_array($ids) && count($ids) > 0 ) {
				$service = StatisticsService::instance();
				$deleted = 0;
				foreach ( $ids as $single_id ) {
					if ( $service->deleteSearch( (int) $single_id ) ) {
						$deleted++;
					}
				}
				return new WP_REST_Response( array( 'deleted' => $deleted ), 200 );
			}

			if ( $id === null ) {
				throw new Exception('Search ID is required.');
			}
			return new WP_REST_Response(
				StatisticsService::instance()->deleteSearch( (int) $id ),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('statistics_latest_searches', $e->getMessage());
		}
	}

	public function saveStatisticsOptions( WP_REST_Request $request ) {
		try {
			$params  = $request->get_json_params();
			$options = StatisticsOptions::instance();
			$options->setArgs($params)
				->save()
				->load(); // Reload data from DB just to be sure it is stored
			return new WP_REST_Response(
				$options,
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('statistics_options_save', $e->getMessage());
		}
	}

	public function resetStatisticsOptions( WP_REST_Request $request ) {
		try {
			$options = StatisticsOptions::instance();
			$options->saveDefaults()->load();
			return new WP_REST_Response(
				$options,
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('statistics_options_reset', $e->getMessage());
		}
	}
	public function resetStatistics( WP_REST_Request $request ) {
		try {
			StatisticsService::instance()->reset();
			return new WP_REST_Response(
				array(),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('statistics_reset', $e->getMessage());
		}
	}

	public function getRealtimeStats() {
		try {
			return new WP_REST_Response(
				StatisticsService::instance()->search_query->getRealtimeStats(),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error('statistics_options_get', $e->getMessage());
		}
	}
}
