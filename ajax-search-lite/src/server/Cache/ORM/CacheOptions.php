<?php

namespace WPDRMS\ASL\Cache\ORM;

use WPDRMS\AdminUI\Data\AbstractOptionDataSiteOption;
use WPDRMS\AdminUI\Options\BoolOption;
use WPDRMS\AdminUI\Options\IntOption;
use WPDRMS\AdminUI\Options\SelectOption;

/**
 * Cache options
 */
final class CacheOptions extends AbstractOptionDataSiteOption {
	public BoolOption $status;
	public SelectOption $cache_type;
	public BoolOption $clear_on_save;
	public IntOption $results_max_age;
	public BoolOption $crop_images;
	public IntOption $images_max_age;

	protected const OPTION_NAME = 'asp_search_cache_options';

	protected const OPTIONS = array(
		'status'          => array(
			'type'         => 'bool',
			'default_args' => array(
				'value' => false,
			),
		),
		'cache_type'      => array(
			'type'         => 'select',
			'default_args' => array(
				'value'   => 'super_file',
				'options' => array(
					'file',
					'super_file',
					'database',
				),
			),
		),
		'clear_on_save'   => array(
			'type'         => 'bool',
			'default_args' => array(
				'value' => true,
			),
		),
		'results_max_age' => array(
			'type'         => 'int',
			'default_args' => array(
				'value' => 604800,
				'min'   => 1,
				'max'   => 2147483647,
			),
		),
		'crop_images'     => array(
			'type'         => 'bool',
			'default_args' => array(
				'value' => false,
			),
		),
	);
}
