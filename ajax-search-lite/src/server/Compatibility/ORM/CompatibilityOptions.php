<?php

namespace WPDRMS\ASL\Compatibility\ORM;

use WPDRMS\AdminUI\Data\AbstractOptionDataSiteOption;
use WPDRMS\AdminUI\Options\BoolOption;
use WPDRMS\AdminUI\Options\SelectOption;

/**
 * Merged compatibility + performance options (excluding image_cropping — handled by CacheOptions).
 */
final class CompatibilityOptions extends AbstractOptionDataSiteOption {
	public SelectOption $js_source;
	public SelectOption $script_loading_method;
	public SelectOption $db_force_case;
	public BoolOption $init_instances_inviewport_only;
	public BoolOption $detect_ajax;
	public BoolOption $load_google_fonts;
	public BoolOption $query_soft_check;
	public BoolOption $use_acf_getfield;
	public BoolOption $db_force_unicode;
	public BoolOption $db_force_utf8_like;
	public BoolOption $use_custom_ajax_handler;
	public BoolOption $load_in_footer;

	protected const OPTION_NAME = 'asl_compatibility_options';

	protected const OPTIONS = array(
		'js_source'                      => array(
			'type'         => 'select',
			'default_args' => array(
				'value'         => 'jqueryless-min',
				'default_value' => 'jqueryless-min',
				'options'       => array( 'jqueryless-nomin', 'jqueryless-min' ),
			),
		),
		'script_loading_method'          => array(
			'type'         => 'select',
			'default_args' => array(
				'value'         => 'classic',
				'default_value' => 'classic',
				'options'       => array( 'classic', 'optimized', 'optimized_async' ),
			),
		),
		'db_force_case'                  => array(
			'type'         => 'select',
			'default_args' => array(
				'value'         => 'none',
				'default_value' => 'none',
				'options'       => array( 'none', 'sensitivity', 'insensitivity' ),
			),
		),
		'init_instances_inviewport_only' => array(
			'type'         => 'bool',
			'default_args' => array( 'value' => true ),
		),
		'detect_ajax'                    => array(
			'type'         => 'bool',
			'default_args' => array( 'value' => true ),
		),
		'load_google_fonts'              => array(
			'type'         => 'bool',
			'default_args' => array( 'value' => true ),
		),
		'query_soft_check'               => array(
			'type'         => 'bool',
			'default_args' => array( 'value' => false ),
		),
		'use_acf_getfield'               => array(
			'type'         => 'bool',
			'default_args' => array( 'value' => false ),
		),
		'db_force_unicode'               => array(
			'type'         => 'bool',
			'default_args' => array( 'value' => false ),
		),
		'db_force_utf8_like'             => array(
			'type'         => 'bool',
			'default_args' => array( 'value' => false ),
		),
		'use_custom_ajax_handler'        => array(
			'type'         => 'bool',
			'default_args' => array( 'value' => false ),
		),
		'load_in_footer'                 => array(
			'type'         => 'bool',
			'default_args' => array( 'value' => true ),
		),
	);
}
