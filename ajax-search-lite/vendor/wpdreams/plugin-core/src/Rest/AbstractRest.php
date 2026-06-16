<?php

namespace WPDRMS\PluginCore\Rest;

use WP_Error;
use WP_REST_Request;
use WPDRMS\PluginCore\Traits\SingletonTrait;

/**
 * Options Rest service
 *
 * NONCE verification is NOT needed, as authentication is done via the X-WP-Nonce header automatically.
 * It is sufficient to check the user status to properly authenticate in the permission callback.
 */
abstract class AbstractRest implements RestInterface {
	use SingletonTrait;

	const ROUTE_NAMESPACE = 'wpdrms_plugin_core';

	/**
	 * A permission callback to restrict rest request to logged in users only
	 *
	 * @param WP_REST_Request|null $request The current request (passed by WordPress to permission callbacks).
	 * @return true|WP_Error
	 */
	public function allowOnlyLoggedIn( $request = null ) {
		/**
		 * Filters whether to bypass the "logged in only" restriction for a REST request.
		 *
		 * Loosen-only: returning true grants access; returning false (default) lets the
		 * normal check run, so the filter can never tighten access. Subscribers receive the
		 * current request and should scope their decision (e.g. to read-only methods).
		 *
		 * @param bool                 $allow   Whether to bypass the restriction. Default false.
		 * @param WP_REST_Request|null $request The current request.
		 */
		if ( apply_filters( 'wpdrms/core/rest/allow_only_logged_in', false, $request ) ) {
			return true;
		}
		if ( !is_user_logged_in() ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'Only logged in users can access this resource.' ), array( 'status' => 401 ) );
		}
		return true;
	}

	/**
	 * A permission callback to restrict rest request to administrator users only
	 *
	 * @param WP_REST_Request|null $request The current request (passed by WordPress to permission callbacks).
	 * @return true|WP_Error
	 */
	public function allowOnlyAdmins( $request = null ) {
		/**
		 * Filters whether to bypass the administrator-only restriction for a REST request.
		 *
		 * Loosen-only: returning true grants access; returning false (default) lets the
		 * normal capability check run, so the filter can never tighten access. Subscribers
		 * receive the current request and should scope their decision (e.g. to read-only
		 * methods) — see WPDRMS\ASP\Misc\DemoMode for the demo read-access use case.
		 *
		 * @param bool                 $allow   Whether to bypass the restriction. Default false.
		 * @param WP_REST_Request|null $request The current request.
		 */
		if ( apply_filters( 'wpdrms/core/rest/allow_only_admins', false, $request ) ) {
			return true;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'Only administrators can access this resource.' ), array( 'status' => 401 ) );
		}
		return true;
	}
}
