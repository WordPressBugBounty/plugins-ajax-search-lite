<?php
namespace WPDRMS\ASL\Search;

use WPDRMS\ASL\Cache\ORM\CacheOptions;
use WPDRMS\ASL\Suggest\KeywordSuggest;
use WPDRMS\Utils\Str;

defined('ABSPATH') || die("You can't access this file directly.");


class SearchQuery {

	public $posts;


	public $all_results;

	/**
	 * The real overall results count
	 *
	 * @var int
	 */
	public $found_posts = 0;

	/**
	 * The returned number of results for this stack
	 *
	 * @var int
	 */
	public $returned_posts = 0;

	/**
	 * The options passed from the search form, when requested from the front-end
	 *
	 * @var array
	 */
	public $options = array();

	public SearchQueryArgs $args;


	private $final_phrases = array();

	/**
	 * @param array<string, mixed> $query_args
	 * @param int                  $search_id
	 * @param array<string, mixed> $options
	 */
	public function __construct( array $query_args, int $search_id = -1, array $options = array() ) {
		if ( $search_id > -1 ) {
			// Translate search data and options to args
			// args priority $args > $search_args > $defaults
			$search_args = \ASL_Helpers::toQueryArgs($search_id, $options, $query_args);
			$this->args  = new SearchQueryArgs($search_args);
		} else {
			// No search instance, use default args
			$this->args = new SearchQueryArgs($query_args);
		}

		// Store the options for later use
		$this->options = $options;

		$this->preProcessArgs();

		$this->args = apply_filters('asl_query_args', $this->args, $search_id, $options);
		do_action('asl_before_search', $this->args['s']);

		$this->args['s'] = apply_filters('asl_search_phrase_before_cleaning', $this->args['s']);
		$this->args['s'] = Str::clear($this->args['s']);
		$this->args['s'] = apply_filters('asl_search_phrase_after_cleaning', $this->args['s']);

		$this->processArgs();
		$this->get_posts();
	}

	private function preProcessArgs(): void {
		if ( !$this->args->_ajax_search && $this->args->_page_id === 0 ) {
			$page_id              = get_the_ID();
			$this->args->_page_id = $page_id === false ? 0 : $page_id;
		}
		if ( empty($this->args->_selected_blogs) ) {
			$this->args->_selected_blogs = array( 0 => get_current_blog_id() );
		}
	}

	private function processArgs(): void {
		$args = $this->args;
		// ---------------- Part 1. Query variables --------------------

		// Do not allow private posts for non-editors
		if ( !current_user_can('read_private_posts') ) {
			$args->post_status = array_diff($args->post_status, array( 'private' ));
		}

		if ( $args->limit > 0 && count($args->search_type) > 0 ) {
			$args->_limit = (int) floor($args->limit /count($args->search_type));
		}

		if ( $args->posts_per_page === 0 ) {
			$args->posts_per_page = (int) get_option('posts_per_page');
		} elseif ( $args->posts_per_page < 0 ) {
			$args->posts_per_page = 999999; // @phpcs:ignore
		}

		$args->keyword_logic = strtolower($args->keyword_logic);

		// Primary order is a meta field
		if (
			$args->post_primary_order_metatype !== '' &&
			$args->_post_primary_order_metakey === '' // this might be set in the helpers class, the nothing to do
		) {
			preg_match('/(.*?) +(.*)/', $args->post_primary_order, $match);
			if ( isset($match[2]) ) {
				// 'field DESC' to 'customfp DESC'
				$args->post_primary_order          = 'customfp ' . ( strtolower($match[2]) === 'asc' ? 'ASC' : 'DESC' );
				$args->_post_primary_order_metakey = $match[1];
			}
		}

		// Secondary order is a meta field
		if (
			$args->post_secondary_order_metatype !== '' &&
			$args->_post_secondary_order_metakey === '' // this might be set in the helpers class, the nothing to do
		) {
			preg_match('/(.*?) +(.*)/', $args->post_secondary_order, $match);
			if ( isset($match[2]) ) {
				// 'field DESC' to 'customfs DESC'
				$args->post_secondary_order          = 'customfs ' . ( strtolower($match[2]) === 'asc' ? 'ASC' : 'DESC' );
				$args->_post_secondary_order_metakey = $match[1];
			}
		}

		// Woocommerce - Excluded catalogue or search products, when variations are selected
		if (
			in_array('product_variation', $args->post_type, true) &&
			wd_in_array_r('product_visibility', $args->post_tax_filter)
		) {
			foreach ( $args->post_tax_filter as $items ) {
				if ( $items['taxonomy'] === 'product_visibility' && !empty($items['exclude']) ) {
					// @phpcs:disable
					/**
					 * @var int[] $product_ids
					 */
					$product_ids = get_posts(
						array(
							'post_type'   => 'product',
							'numberposts' => 250,
							'tax_query'   => array(
								array(
									'taxonomy' => 'product_visibility',
									'field'    => 'id',
									'terms'    => $items['exclude'],
									'operator' => 'IN',
								),
							),
							'fields'      => 'ids',  // Only get post IDs
						)
					);
					// @phpcs:enable
					if ( !is_wp_error($product_ids) && !empty($product_ids) ) {
						$args->post_parent_exclude = array_unique( array_merge($args->post_parent_exclude, $product_ids) );
					}
					break;
				}
			}
		}

		// ----------------- User search Stuff -------------------------
		// Primary order is a meta field on user search
		if (
			$args->user_primary_order_metatype !== '' &&
			$args->_user_primary_order_metakey === '' // this might be set in the helpers class, the nothing to do
		) {
			preg_match('/(.*?) +(.*)/', $args->user_primary_order, $match);
			if ( isset($match[2]) ) {
				// 'field DESC' to 'customfp DESC'
				$args->user_primary_order          = 'customfp ' . ( strtolower($match[2]) === 'asc' ? 'ASC' : 'DESC' );
				$args->_user_primary_order_metakey = $match[1];
			}
		}
		// Secondary order is a meta field on user search
		if (
			$args->user_secondary_order_metatype !== '' &&
			$args->_user_secondary_order_metakey === '' // this might be set in the helpers class, the nothing to do
		) {
			preg_match('/(.*?) +(.*)/', $args->user_secondary_order, $match);
			if ( isset($match[2]) ) {
				// 'field DESC' to 'customfs DESC'
				$args->user_secondary_order          = 'customfs ' . ( strtolower($match[2]) === 'asc' ? 'ASC' : 'DESC' );
				$args->_user_secondary_order_metakey = $match[1];
			}
		}
		// ------------------ Part 2. Search data ----------------------

		// Break after this point, if no search data is provided
		if ( empty($this->args['_sd']) ) {
			return;
		}

		$sd = &$this->args['_sd'];

		$search_type_by_result_type = array(
			'bp_activities'     => 'buddypress',
			'bp_groups'         => 'buddypress',
			'blogs'             => 'blogs',
			'bp_users'          => 'users',
			'peepso_groups'     => 'peepso_groups',
			'peepso_activities' => 'peepso_activities',
			'terms'             => 'taxonomies',
			'post_page_cpt'     => 'cpt',
			'comments'          => 'comments',
			'attachments'       => 'attachments',
		);
		$results_order              = $args->_sd['results_order'] ?? 'post_page_cpt';
		if ( strpos($results_order, 'attachments') === false ) {
			$results_order .= '|attachments';
		}

		$results_order_arr    = explode('|', $results_order);
		$ordered_search_types = array();
		foreach ( $results_order_arr as $result_type ) {
			if ( !isset($search_type_by_result_type[ $result_type ]) ) {
				continue;
			}
			$search_type = $search_type_by_result_type[ $result_type ];
			if ( in_array($search_type, $args->search_type, true) ) {
				$ordered_search_types[] = $search_type;
			}
		}
		$args->search_type = array_unique($ordered_search_types);

		$args->_charcount = (int) $sd['charcount'];

		$sd['image_options'] = array(
			'image_cropping'        => CacheOptions::instance()->crop_images->value,
			'show_images'           => $sd['show_images'],
			'apply_content_filter'  => $sd['image_apply_content_filter'],
			'image_width'           => $sd['image_width'],
			'image_height'          => $sd['image_height'],
			'image_source1'         => $sd['image_source1'],
			'image_source2'         => $sd['image_source2'],
			'image_source3'         => $sd['image_source3'],
			'image_source4'         => $sd['image_source4'],
			'image_source5'         => $sd['image_source5'],
			'image_default'         => $sd['image_default'],
			'image_source_featured' => $sd['image_source_featured'],
			'image_custom_field'    => $sd['image_custom_field'],
		);

		if ( isset($_POST['asl_get_as_array']) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$sd['image_options']['show_images'] = 0;
		}

		// ----------------- Recalculate image width/height ---------------
		switch ( $sd['resultstype'] ) {
			case 'horizontal':
				/* Same width as height */
				$sd['image_options']['image_width']  = intval($sd['h_image_height']);
				$sd['image_options']['image_height'] = intval($sd['h_image_height']);
				break;
			case 'polaroid':
				$sd['image_options']['image_width']  = (int) ( $sd['preswidth'] );
				$sd['image_options']['image_height'] = (int) ( $sd['preswidth'] );
				break;
			case 'isotopic':
				if ( strpos($sd['i_item_width'], '%') === false ) {
					$sd['image_options']['image_width'] = (int) ( (int) $sd['i_item_width'] * 1.5 );
				} else {
					$sd['image_options']['image_width'] = (int) ( 1920 / ( 100 / (int) $sd['i_item_width'] ) );
				}
				if ( strpos($sd['i_item_height'], '%') === false ) {
					$sd['image_options']['image_height'] = (int) ( (int) $sd['i_item_height'] * 1.5 );
				} else {
					$sd['image_options']['image_height'] = (int) ( 1920 / ( 100 / (int) $sd['i_item_height'] ) );
				}
				break;
		}
		if ( empty($sd['image_options']['image_width']) ) {
			$sd['image_options']['image_width'] = 300;
		}
		if ( empty($sd['image_options']['image_height']) ) {
			$sd['image_options']['image_height'] = 300;
		}

		// Disable image cropping in non-ajax mode
		if ( !$args->_ajax_search ) {
			$sd['image_options']['image_cropping'] = 0;
		}
	}

	public function getArgs(): SearchQueryArgs {
		return $this->args;
	}

	public function get_posts() {
		$args  = $this->args;
		$_args = $args; // copy to store changes

		$ra = array(
			'blogresults'        => array(),

			'allbuddypresults'   => array(
				'groupresults'    => array(),
				'activityresults' => array(),
			),

			'alltermsresults'    => array(),
			'allpageposts'       => array(),
			'pageposts'          => array(),
			'repliesresults'     => array(),
			'allcommentsresults' => array(),
			'commentsresults'    => array(),
			'userresults'        => array(),
			'attachment_results' => array(),
			'peepso_groups'      => array(),
			'peepso_activities'  => array(),
		);

		$s = $this->applyTransformations( $args->s );

		// Allow even empty phrases, it should not be limited via server side
		$this->final_phrases[] = $s;
		$this->final_phrases   = apply_filters('asl_final_phrases', $this->final_phrases);

		$logics = array( $args->keyword_logic );
		if ( !empty($args->secondary_logic) && $args->secondary_logic !== 'none' && $args->_call_num === 0 ) {
			$logics[] = strtolower($args->secondary_logic);
		}

		$it_results_start_offset = 0;

		foreach ( $args->search_type as $search_type ) {
			// ---- Search Porcess Starts Here ----
			foreach ( $this->final_phrases as $s ) {

				foreach ( $args->_selected_blogs as $blog ) {
					if ( is_multisite() ) {
						switch_to_blog($blog);
					}

					if ( $search_type === 'cpt' && count($args->post_type) > 0 ) {
						if ( $args->posts_limit_distribute ) {
							if ( !empty($args->_sd) && $args->_sd['use_post_type_order'] ) {
								$_temp_ptypes = array();
								foreach ( $args->_sd['post_type_order'] as $p_order ) {
									if ( in_array($p_order, $args->post_type, true) ) {
										$_temp_ptypes[] = $p_order;
									}
								}
								$_temp_ptypes = array_unique(array_merge($_temp_ptypes, $args->post_type));
							} else {
								$_temp_ptypes = $args->post_type;
							}

							$_temp_ptype_limits = array();

							foreach ( $_temp_ptypes as $_tptype ) {
								$_temp_ptype_limits[ $_tptype ] = array(
									(int) ( $args->posts_limit / count($_temp_ptypes) ),
									(int) ( $args->posts_limit_override / count($_temp_ptypes) ),
								);
							}

							// Reset this at each loop, as post IDs can be the same across multisite
							$args->post_not_in2 = array();
							foreach ( $_temp_ptypes as $_tptype ) {
								foreach ( $logics as $lk => $logic ) {
									if ( $lk === 0 && $args->_exact_matches ) {
										// If exact matches is on, disregard the firs logic
										$args->keyword_logic = 'or';
									} else {
										if ( $lk > 0 ) {
											$args->_exact_matches = false;
										}
										$args->keyword_logic = $logic;
									}
									$args->post_type = array( $_tptype );
									// Change the limits temporarly for the search
									$args->posts_limit          = $_temp_ptype_limits[ $_tptype ][0];
									$args->posts_limit_override = $_temp_ptype_limits[ $_tptype ][1];
									$args->global_found_posts   = $this->found_posts;
									// For exact matches the regular engine is used
									$_posts     = new SearchPostTypes($args);
									$_posts_res = $_posts->search($s);
									// Adjust the relevance by R + (N x 1 000 000) as a grouping feature for mutliple logics
									// First logic matches will be more relevant this way
									foreach ( $_posts_res as &$posts_re ) {
										$posts_re->relevance = $posts_re->relevance + ( ( count($logics) - $lk ) * 1000000 );
									}
									$ra['allpageposts'] = array_merge($ra['allpageposts'], $_posts_res);
									if ( $lk > 0 ) {
										$this->found_posts += $_posts->return_count;
									} else {
										$this->found_posts += $_posts->results_count;
									}
									$it_results_start_offset           += $_posts->start_offset;
									$_temp_ptype_limits[ $_tptype ][0] -= $_posts->return_count;
									$_temp_ptype_limits[ $_tptype ][1] -= $_posts->return_count;
									$args->post_not_in2                 = array_merge($args->post_not_in2, $this->getResIdsArr($_posts_res));
									$args->_exact_matches               = $_args['_exact_matches'];
								}
							}
							$args->post_type = $_temp_ptypes;
						} else {
							// Reset this at each loop, as post IDs can be the same across multisite
							$args->post_not_in2 = array();
							foreach ( $logics as $lk => $logic ) {
								if ( $lk === 0 && $args->_exact_matches ) {
									// If exact matches is on, disregard the first logic
									$args->keyword_logic = 'or';
								} else {
									if ( $lk > 0 ) {
										$args->_exact_matches = false;
									}
									$args->keyword_logic = $logic;
								}

								$args->global_found_posts = $this->found_posts;

								$_posts     = new SearchPostTypes($args);
								$_posts_res = $_posts->search($s);
								foreach ( $_posts_res as &$posts_re ) {
									$posts_re->relevance = $posts_re->relevance + ( ( count($logics) - $lk ) * 1000000 );
								}
								$ra['allpageposts'] = array_merge($ra['allpageposts'], $_posts_res);

								if ( $lk > 0 ) {
									$this->found_posts += $_posts->return_count;
								} else {
									$this->found_posts += $_posts->results_count;
								}
								$it_results_start_offset    += $_posts->start_offset;
								$args->posts_limit          -= $_posts->return_count;
								$args->posts_limit_override -= $_posts->return_count;
								$args->post_not_in2          = array_merge($args->post_not_in2, $this->getResIdsArr($_posts_res));
								$args->_exact_matches        = $_args['_exact_matches'];
							}
						}

						do_action('asl_after_pagepost_results', $s, $ra['allpageposts']);
					}

					if ( is_multisite() ) {
						restore_current_blog();
					}
				}           
			}
			// ---- Search Porcess Stops Here ----
		}
		$results_count_adjust = 0;  // Count the changes in results count when using filters

		$rca                   = count($ra['allpageposts']);
		$ra['allpageposts']    = apply_filters('asl_pagepost_results', $ra['allpageposts'], $args->_id, $args);
		$ra['allpageposts']    = apply_filters('asl_cpt_results', $ra['allpageposts'], $args->_id, $args);
		$rca                  -= count($ra['allpageposts']);
		$results_count_adjust += $rca;

		SearchPostTypes::orderBy(
			$ra['allpageposts'],
			array(
				'engine'                      => $args->engine,
				'primary_ordering'            => $args->post_primary_order,
				'primary_ordering_metatype'   => $args->post_primary_order_metatype,
				'secondary_ordering'          => $args->post_secondary_order,
				'secondary_ordering_metatype' => $args->post_secondary_order_metatype,
			)
		);

		// Results as array, unordered
		$results_arr = array(
			'terms'             => $ra['alltermsresults'],
			'blogs'             => $ra['blogresults'],
			'bp_activities'     => $ra['allbuddypresults']['activityresults'],
			'comments'          => $ra['allcommentsresults'],
			'bp_groups'         => $ra['allbuddypresults']['groupresults'],
			'bp_users'          => $ra['userresults'],
			'post_page_cpt'     => $ra['allpageposts'],
			'attachments'       => $ra['attachment_results'],
			'peepso_groups'     => $ra['peepso_groups'],
			'peepso_activities' => $ra['peepso_activities'],
		);

		foreach ( $results_arr as $k => $v ) {
			$final = array();
			foreach ( $v as $current ) {
				$found = false;
				foreach ( $final as $item ) {
					if ( $item->id == $current->id && $item->blogid == $current->blogid ) { // phpcs:ignore
						$found = true;
						break;
					}
				}
				if ( !$found ) {
					$final[] = $current;
				}
			}
			$results_arr[ $k ] = $final;
		}

		// Order if search data is set
		if ( !empty($args->_sd) ) {
			$results_order = $args->_sd['results_order'] ?? 'post_page_cpt';

			if ( strpos($results_order, 'attachments') === false ) {
				$results_order .= '|attachments';
			}

			// These keys are in the right order
			$results_order_arr = explode('|', $results_order);

			$results = array();
			foreach ( $results_order_arr as $rv ) {
				if ( isset($results_arr[ $rv ]) ) {
					$results = array_merge($results, $results_arr[ $rv ]);
				}
			}
			$rca                   = count($results);
			$results               = apply_filters('asl_results', $results, $args->_id, $args->_ajax_search, $args);
			$rca                  -= count($results);
			$results_count_adjust += $rca;
		} else {
			$results = array();
			foreach ( $results_arr as $rv ) {
				$results = array_merge($results, $rv);
			}
			$rca                   = count($results);
			$results               = apply_filters('asl_results', $results, -1, false, $args);
			$rca                  -= count($results);
			$results_count_adjust += $rca;
		}

		// $results_count_adjust > 0 -> posts have been removed, otherwise added
		$this->found_posts -= $results_count_adjust;
		$this->found_posts  = $this->found_posts < 0 ? 0 : $this->found_posts; // Make sure this is not 0

		$this->all_results = $results;

		// For non-ajax searches, we need the WP_Post objects
		if ( !$args->_ajax_search ) {
			if ( count($results) > $args->posts_per_page ) {
				$results = asl_results_to_wp_obj($results, ( $args->posts_per_page * ( $args->page - 1 ) ) - $it_results_start_offset, $args->posts_per_page);
			} else {
				$results = asl_results_to_wp_obj($results, 0, $args->posts_per_page);
			}
			$results = apply_filters('asl_noajax_results', $results, $args->_id, false, $args);
		}

		if ( isset($results['groups']) ) {
			foreach ( $results['groups'] as $group ) {
				if ( isset($group['items']) ) {
					$this->returned_posts += count($group['items']);
				}
			}
		} else {
			$this->returned_posts = count($results);
		}

		apply_filters('asl/search/results', $results, $args, $this);

		$this->posts = $results;
		return $results;
	}


	public function kwSuggestions( $single = false ) {
		if ( !isset($this->args['_sd'], $this->args['_id']) ) {
			return array();
		}

		$keywords = array();
		$types    = array();
		$sd       = &$this->args['_sd'];
		$args     = $this->args;
		$results  = array();
		$count    = $single === false ? $sd['kw_count'] : 1;

		if ( isset($sd['customtypes']) && count($sd['customtypes']) > 0 ) {
			$types = array_merge($types, $sd['customtypes']);
		}

		if ( function_exists( 'qtranxf_use' ) && $args->_qtranslate_lang !== '' ) {
			$lang = $args->_qtranslate_lang;
		} elseif ( $args->_wpml_lang !== '' ) {
			$lang = $args->_wpml_lang;
		} elseif ( $args->_polylang_lang !== '' ) {
			$lang = $args->_polylang_lang;
		} else {
			$lang = $sd['kw_google_lang'];
		}

		$phrase = trim($this->args['s']);
		if ( $phrase !== '' ) {
			foreach ( array( 'google' ) as $source ) {
				if ( empty($source) ) {
					continue;
				}
				$remaining_count = $count - count($keywords);
				if ( $remaining_count <= 0 ) {
					break;
				}

				$taxonomy = '';
				// Check if this is a taxonomy
				if ( strpos($source, 'xtax_') !== false ) {
					$taxonomy = str_replace('xtax_', '', $source);
					$source   = 'terms';
				}

				$t = new KeywordSuggest(
					$source,
					array(
						'maxCount'        => $remaining_count,
						'maxCharsPerWord' => $sd['kw_length'],
						'postTypes'       => $types,
						'lang'            => $lang,
						'overrideUrl'     => '',
						'taxonomy'        => $taxonomy,
						'search_id'       => $this->args['_id'],
						'options'         => $this->options,
						'args'            => (array) $args,
					)
				);

				$keywords = array_merge($keywords, $t->getKeywords($phrase));
			}
		}

		if ( !empty($keywords) ) {
			$results['keywords'] = $keywords;
			$results['nores']    = 1;
			$results             = apply_filters('asl_only_keyword_results', $results);
		}

		return $results;
	}

	private function applyTransformations( $s ) {
		if ( empty($this->args['_sd']) ) {
			return $s;
		}

		$sd = &$this->args['_sd'];

		if ( $sd['kw_exceptions'] !== '' ) {
			$exceptions = stripslashes( str_replace(array( ' ,', ', ', ' , ' ), ',', $sd['kw_exceptions']) );
			if ( $exceptions !== '' ) {
				$s = trim(str_ireplace(explode(',', $exceptions), '', $s));
				$s = preg_replace('/\s+/', ' ', $s);
			}
		}

		if ( $sd['kw_exceptions_e'] !== '' ) {
			$exceptions = stripslashes( str_replace(array( ' ,', ', ', ' , ' ), ',', $sd['kw_exceptions_e']) );
			$exceptions = explode(',', $exceptions);
			foreach ( $exceptions as &$v ) {
				$v = '/\b' . $v . '\b/ui';
			}
			unset($v);
			if ( count($exceptions) > 0 ) {
				$s = trim(preg_replace($exceptions, '', $s));
				$s = preg_replace('/\s+/', ' ', $s);
			}
		}

		if ( $this->args->transform_quotes ) {
			$s = preg_replace('/[\x{201C}\x{201D}\x{201E}\x{2033}]/u', '"', $s);
			$s = preg_replace('/[\x{2018}\x{2019}\x{201A}\x{201B}\x{2032}]/u', "'", $s);
		}

		// WordPress stores & as &amp; This must be reverted on index table.
		$s = str_replace('&', '&amp;', $s);

		return $s;
	}

	private function getResIdsArr( $r ): array {
		$ret = array();
		if ( is_array($r) ) {
			foreach ( $r as $v ) {
				if ( isset($v->id) ) {
					$ret[] = $v->id;
				}
			}
		}
		return $ret;
	}
}
