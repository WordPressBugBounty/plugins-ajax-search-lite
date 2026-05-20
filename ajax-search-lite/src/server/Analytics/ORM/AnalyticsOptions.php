<?php

namespace WPDRMS\ASL\Analytics\ORM;

use WPDRMS\AdminUI\Data\AbstractOptionDataSiteOption;
use WPDRMS\AdminUI\Options\BoolOption;
use WPDRMS\AdminUI\Options\EventConfigArrayOption;
use WPDRMS\AdminUI\Options\StringOption;

/**
 * Analytics options — stores GA/GTM event configuration per trigger point.
 *
 * The 7 trigger properties are statically typed as EventConfigArrayOption so each
 * can hold an arbitrary number of independently-named event configs.
 */
final class AnalyticsOptions extends AbstractOptionDataSiteOption {
	public BoolOption $method;
	public StringOption $tracking_id;
	public EventConfigArrayOption $focus;
	public EventConfigArrayOption $search_start;
	public EventConfigArrayOption $search_end;
	public EventConfigArrayOption $magnifier;
	public EventConfigArrayOption $return;
	public EventConfigArrayOption $facet_change;
	public EventConfigArrayOption $result_click;

	protected const OPTION_NAME = 'asp_analytics_options';

	protected const OPTIONS = array(
		'method'       => array(
			'type'         => 'bool',
			'default_args' => array( 'value' => false ),
		),
		'tracking_id'  => array(
			'type'         => 'string',
			'default_args' => array(
				'value' => '',
			),
		),
		'focus'        => array(
			'type'         => 'event_config_array',
			'default_args' => array( 'items' => array() ),
		),
		'search_start' => array(
			'type'         => 'event_config_array',
			'default_args' => array( 'items' => array() ),
		),
		'search_end'   => array(
			'type'         => 'event_config_array',
			'default_args' => array( 'items' => array() ),
		),
		'magnifier'    => array(
			'type'         => 'event_config_array',
			'default_args' => array( 'items' => array() ),
		),
		'return'       => array(
			'type'         => 'event_config_array',
			'default_args' => array( 'items' => array() ),
		),
		'facet_change' => array(
			'type'         => 'event_config_array',
			'default_args' => array( 'items' => array() ),
		),
		'result_click' => array(
			'type'         => 'event_config_array',
			'default_args' => array( 'items' => array() ),
		),
	);
}
