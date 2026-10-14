<?php

namespace WPDRMS\ASL\Utils;

use WP_Error;

/**
 * For REST routes that change data shared by every site of a network: the plugin options are site
 * options and its tables use the network prefix. On a multisite only a network admin may change them,
 * otherwise a sub-site admin could wipe or reset another site's data.
 *
 * Used with \WPDRMS\PluginCore\Rest\AbstractRest (allowOnlyAdmins()).
 */
trait NetworkAdminOnly {
	/**
	 * @param \WP_REST_Request|null $request
	 * @return true|WP_Error
	 */
	public function allowOnlyNetworkAdmins( $request = null ) {
		$allowed = $this->allowOnlyAdmins($request);
		if ( $allowed !== true || !is_multisite() || current_user_can('manage_network_options') ) {
			return $allowed;
		}
		return new WP_Error(
			'rest_forbidden',
			__('Only a network administrator can change the settings and data shared by every site.', 'ajax-search-lite'),
			array( 'status' => 403 )
		);
	}
}
