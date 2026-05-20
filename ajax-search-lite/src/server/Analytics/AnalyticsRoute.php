<?php

namespace WPDRMS\ASL\Analytics;

use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WPDRMS\ASL\Analytics\ORM\AnalyticsOptions;
use WPDRMS\PluginCore\Rest\AbstractRest;

class AnalyticsRoute extends AbstractRest {
	public function registerRoutes( string $route_namespace ): void {
		register_rest_route(
			$route_namespace,
			'options/analytics',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'getOptions' ),
					'permission_callback' => array( $this, 'allowOnlyAdmins' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'saveOptions' ),
					'permission_callback' => array( $this, 'allowOnlyAdmins' ),
				),
			),
		);

		register_rest_route(
			$route_namespace,
			'options/analytics/reset',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'resetOptions' ),
				'permission_callback' => array( $this, 'allowOnlyAdmins' ),
			),
		);
	}

	/** @return WP_Error|WP_REST_Response */
	public function getOptions() {
		try {
			return new WP_REST_Response(AnalyticsOptions::instance(), 200);
		} catch ( Exception $e ) {
			return new WP_Error('analytics_options_get', $e->getMessage());
		}
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function saveOptions( WP_REST_Request $request ) {
		try {
			$params  = self::sanitizeItems($request->get_json_params());
			$options = AnalyticsOptions::instance();
			$options->setArgs($params)->save()->load();
			return new WP_REST_Response($options, 200);
		} catch ( Exception $e ) {
			return new WP_Error('analytics_options_save', $e->getMessage());
		}
	}

	private static function sanitizeItems( array $params ): array {
		$triggers = array( 'focus', 'search_start', 'search_end', 'magnifier', 'return', 'facet_change', 'result_click' );
		foreach ( $triggers as $trigger ) {
			if ( !isset($params[ $trigger ]['items']) ) {
				continue;
			}
			$clean = array();
			foreach ( $params[ $trigger ]['items'] as $item ) {
				$action = preg_replace('/[^a-zA-Z0-9_]/', '', trim((string) ( $item['action'] ?? '' )));
				if ( $action === '' ) {
					continue;
				}
				$clean_params = array();
				foreach ( $item['params'] ?? array() as $p ) {
					$key = preg_replace('/[^a-zA-Z0-9_]/', '', trim((string) ( $p['key'] ?? '' )));
					$val = trim((string) ( $p['value'] ?? '' ));
					if ( $key === '' || $val === '' ) {
						continue;
					}
					$clean_params[] = array( 'key' => $key, 'value' => $p['value'] );
				}
				$clean[] = array_merge($item, array( 'action' => $action, 'params' => $clean_params ));
			}
			$params[ $trigger ]['items'] = $clean;
		}
		return $params;
	}

	/** @return WP_Error|WP_REST_Response */
	public function resetOptions() {
		try {
			$options = AnalyticsOptions::instance();
			$options->saveDefaults()->load();
			return new WP_REST_Response($options, 200);
		} catch ( Exception $e ) {
			return new WP_Error('analytics_options_reset', $e->getMessage());
		}
	}
}
