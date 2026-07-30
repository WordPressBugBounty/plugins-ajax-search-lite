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

	/** Per-instance REST namespace, set by the route's registerRoutes(); drives the per-plugin filter tags. */
	protected string $route_namespace = self::ROUTE_NAMESPACE;

	/** A passed namespace is honoured only if it is a clean token; otherwise fall back (callers historically pass garbage, e.g. a directory path). */
	protected function sanitizeNamespace( string $namespace ): string {
		return preg_match( '/^[a-z0-9_-]+$/', $namespace ) ? $namespace : self::ROUTE_NAMESPACE;
	}

	/** The per-plugin token ('snipcraft' / default 'wpdrms') derived from the route namespace. */
	protected function vendorToken(): string {
		$token = (string) preg_replace( '/_plugin_core$/', '', $this->route_namespace );
		return $token !== '' ? $token : 'wpdrms';
	}

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
		if ( apply_filters( $this->vendorToken() . '/core/rest/allow_only_logged_in', false, $request ) ) {
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
		if ( apply_filters( $this->vendorToken() . '/core/rest/allow_only_admins', false, $request ) ) {
			return true;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'Only administrators can access this resource.' ), array( 'status' => 401 ) );
		}
		return true;
	}
}
