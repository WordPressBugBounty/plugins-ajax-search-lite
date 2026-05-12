<?php
return array(
	'ASL_BACKEND' => array(
		'admin_url'       => admin_url(),
		'ajaxurl'         => admin_url('admin-ajax.php'),
		'home_url'        => home_url('/'),
		'rest_url'        => apply_filters('asl/rest/base_url/', rest_url()),
		'backend_ajaxurl' => admin_url('admin-ajax.php'),
		'asp_url'         => ASL_URL,
		// 'upload_url'      => wd_asl()->upload_url,
		'is_multisite'    => is_multisite(),
		'is_multilang'    => asl_is_multilang(),
		'is_pro_active'   => defined('ASP_DIR'),
	),
);
