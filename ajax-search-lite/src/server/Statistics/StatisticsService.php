<?php

namespace WPDRMS\ASL\Statistics;

use wpdb;
use WPDRMS\ASL\Search\SearchQueryArgs;
use WPDRMS\PluginCore\Traits\SingletonTrait;
use WPDRMS\ASL\Search\SearchQuery as ASP_SearchQuery;
use WPDRMS\ASL\Statistics\Exporters\SearchCSVExporter;
use WPDRMS\ASL\Statistics\ORM\Search;
use WPDRMS\ASL\Statistics\ORM\StatisticsOptions;
use WPDRMS\ASL\Statistics\Queries\InteractionQuery;
use WPDRMS\ASL\Statistics\Queries\ResultQuery;
use WPDRMS\ASL\Statistics\Queries\SearchQuery;
use WPDRMS\Utils\MB;
use WPDRMS\Utils\Server;

/**
 * @phpstan-type Searches array<array{
 *     phrase?: string,
 *     page?: int,
 *     asp_id?: int,
 *     device_type?: string,
 *     lang?: string,
 *     found_results?: int,
 *     user_id?: int,
 *     cached?: 1|0,
 *     suggested?: 1|0,
 *     results?: mixed|array<array{id?: int, result_type?: string}>
 * }>
 */
class StatisticsService {
	use SingletonTrait;

	public StatisticsOptions $options;

	/**
	 * @var array<string, int>
	 */
	public static array $result_type_arr = array(
		'pagepost'        => 1,
		'term'            => 2,
		'user'            => 3,
		'blog'            => 4,
		'bp_group'        => 5,
		'bp_activity'     => 6,
		'comment'         => 7,
		'attachment'      => 8,
		'peepso_activity' => 9,
		'peepso_group'    => 10,
	);

	public static int $last_search_id = 0;

	/**
	 * @var SearchQuery
	 * @readonly
	 */
	public SearchQuery $search_query;

	public ResultQuery $result_query;
	public InteractionQuery $interaction_query;

	public SearchCSVExporter $search_csv_exporter;

	private function __construct() {
		$this->search_query        = SearchQuery::instance();
		$this->result_query        = ResultQuery::instance();
		$this->interaction_query   = InteractionQuery::instance();
		$this->search_csv_exporter = SearchCSVExporter::instance();
	}

	public function loadHooks(): void {
		$this->options = StatisticsOptions::instance();
		if ( $this->options->status->value ) {
			add_action(
				'wp_footer',
				function () {
					?>
					<div id="asp-statistics" data-statistics-id="<?php echo esc_attr(self::$last_search_id); ?>" style="display:none;"></div>
					<?php
				}
			);
			add_action('asl/search/override/args', array( $this, 'searchOverrideArgsHook' ), 99, 4);
			add_filter('asl/search/results', array( $this, 'registerSearchAndResultsHook' ), 99, 3);
			add_action('asp/ajax/search/custom_output', array( $this, 'registerCachedSearchesQueriesHook' ), 9999, 6);
			add_action(
				'asp/statistics/retention',
				function () {
					$this->enforceRetention(
						$this->options->data_retention_age->value,
						$this->options->data_retention_max_searches->value
					);
				}
			);
			if ( ! wp_next_scheduled( 'asp/statistics/retention' ) ) {
				wp_schedule_event( time() + 30, 'hourly', 'asp/statistics/retention' );
			}
		} else {
			if ( wp_next_scheduled( 'asp/statistics/retention' ) ) {
				wp_unschedule_event( wp_next_scheduled( 'asp/statistics/retention' ), 'asp/statistics/retention');
			}
			wp_clear_scheduled_hook('asp/statistics/retention');
		}
	}

	/**
	 *
	 * @uses apply_filters('asp/search/override/args', array $args, array $data);
	 *
	 * @param array $args
	 * @param array $data
	 * @return array
	 */
	public function searchOverrideArgsHook( array $args, array $data ) {
		if ( class_exists('SitePress') && $data['override_count'] === 0 ) {
			$args['record_statistics'] = false;
		}
		return $args;
	}


	/**
	 * @uses apply_filters('asp/search/results', $results, SearchQueryArgs $args, SearchQuery $query);
	 *
	 * @param array           $results
	 * @param SearchQueryArgs $args
	 * @param SearchQuery     $query
	 * @return array
	 */
	public function registerSearchAndResultsHook( array $results, SearchQueryArgs $args, ASP_SearchQuery $query ) {
		if ( !$args->record_statistics ) {
			return $results;
		}

		/**
		 * Exclude requests from admin preview
		 */
		$referer = Server::getCleanReferrerPath();
		if ( str_starts_with($referer ?? '', Server::getCleanUrlPath( get_admin_url() ) ?? 'wp-admin/admin.php' ) ) {
			return $results;
		}

		/**
		 * Excluded keywords
		 */
		if ( $args->s !== '' ) {
			foreach ( $this->options->exclude_phrases_partial->value as $excluded_phrase ) {
				if ( str_contains($args->s, $excluded_phrase) ) {
					return $results;
				}
			}

			if ( in_array($args->s, $this->options->exclude_phrases_whole->value, true) ) {
				return $results;
			}
		}

		$search            = new Search();
		$search->phrase    = MB::substr(sanitize_text_field($args->s), 0, $this->options->max_phrase_length->value);
		$search->type      = $args->_ajax_search ? 1 : 0;
		$search->user_id   = get_current_user_id();
		$search->page      = $args->_ajax_search ? ( $args->_call_num === 0 ? 1 : $args->_call_num + 1 ) : $args->page;
		$search->asp_id    = $args->_id > 0 ? $args->_id : null;
		$search->suggested = $args->is_suggestion_query ? 1 : 0;

		if ( !$this->options->record_suggested_searches->value && $search->suggested !== 0 ) {
			return $results;
		}

		if ( is_multisite() ) {
			$search->blog_id = get_current_blog_id();
		}
		switch ( $args->device_type ) {
			case 'tablet':
				$search->device_type = 2;
				break;
			case 'mobile':
				$search->device_type = 3;
				break;
			default:
				$search->device_type = 1;
		}
		if ( $args->_wpml_lang !== '' ) {
			$search->lang = $args->_wpml_lang;
		} elseif ( $args->_polylang_lang !== '' ) {
			$search->lang = $args->_polylang_lang;
		}
		$search->found_results = $query->found_posts;
		$search->referer       = $referer;
		$search                = $search->save();  // Save and return updated model

		if ( !$search ) {
			return $results;
		}

		self::$last_search_id = $search->id;

		return $results;
	}

	public function registerCachedSearchesQueriesHook( string $output, int $id, string $s, array $options, int $call_num, $autop ): string {
		$data = json_decode($output, true);
		if ( empty($data) ) {
			return $output;
		}
		$search_data = array(
			'phrase'        => $s,
			'page'          => $call_num + 1,
			'asp_id'        => $id,
			'device_type'   => $options['device'] ?? '',
			'lang'          => $data['lang'] ?? '',
			'found_results' => $data['full_results_count'] ?? 0,
			'suggested'     => isset($data['suggested']) ? (int) $data['suggested'] : 0,
			'user_id'       => get_current_user_id(),
			'cached'        => 1,
		);

		$this->registerCachedSearchesQueries(array( $search_data ));

		return $output;
	}


	/**
	 * @param Searches $searches
	 * @return void
	 */
	public function registerCachedSearchesQueries( array $searches ): void {
		if ( empty($searches) || !$this->options->status->value ) {
			return;
		}

		/**
		 * Exclude requests from admin preview
		 */
		$referer = Server::getCleanReferrerPath();
		if ( str_starts_with($referer ?? '', Server::getCleanUrlPath( get_admin_url() ) ?? 'wp-admin/admin.php' ) ) {
			return;
		}

		foreach ( $searches as $search_data ) {
			/**
			 * A search phrase is plain user input arriving on an unauthenticated route.
			 * Coerce it to a string WITHOUT deserializing: never route untrusted input
			 * through Str::anyToString(), which unserializes/JSON-decodes and would expose
			 * a PHP Object Injection vector (php-utils#25). Non-scalar values (arrays or
			 * objects from malformed JSON) are not valid phrases and are dropped.
			 */
			$raw_phrase = $search_data['phrase'] ?? '';
			$phrase     = is_scalar($raw_phrase) ? (string) $raw_phrase : '';
			/**
			 * Excluded keywords
			 */
			if ( $phrase !== '' ) {
				foreach ( $this->options->exclude_phrases_partial->value as $excluded_phrase ) {
					if ( str_contains($phrase, $excluded_phrase) ) {
						continue 2;
					}
				}

				if ( in_array($phrase, $this->options->exclude_phrases_whole->value, true) ) {
					continue;
				}
			}

			$search            = new Search();
			$search->phrase    = MB::substr($phrase, 0, $this->options->max_phrase_length->value);
			$search->type      = 1;
			$search->user_id   = isset($search_data['user_id']) ? intval($search_data['user_id']) : get_current_user_id();
			$search->page      = isset($search_data['page']) ? intval($search_data['page']) : 1;
			$search->asp_id    = isset($search_data['asp_id']) && $search_data['asp_id'] > 0 ? intval($search_data['asp_id']) : null;
			$search->cached    = isset($search_data['cached']) ? intval($search_data['cached']) : 0;
			$search->suggested = isset($search_data['suggested']) ? intval($search_data['suggested']) : 0;

			if ( !$this->options->record_suggested_searches->value && $search->suggested !== 0 ) {
				continue;
			}

			if ( is_multisite() ) {
				$search->blog_id = get_current_blog_id();
			}

			if ( isset($search_data['device_type']) ) {
				if ( is_numeric($search_data['device_type']) ) {
					$search->device_type = intval($search_data['device_type']);
					$search->device_type = max(1, min(3, $search->device_type));
				} else {
					switch ( $search_data['device_type'] ) {
						case 'tablet':
							$search->device_type = 2;
							break;
						case 'mobile':
							$search->device_type = 3;
							break;
						default:
							$search->device_type = 1;
					}
				}
			} else {
				$search->device_type = 1;
			}

			if ( isset($search_data['lang']) && $search_data['lang'] !== '' ) {
				$search->lang = $search_data['lang'];
			}

			$search->found_results = isset($search_data['found_results']) ? intval($search_data['found_results']) : 0;
			$search->referer       = $referer;

			$search->save();
		}
	}

	public function createTables(): void {
		Search::createTable();
	}

	public function dropTables(): void {
		Search::dropTable();
	}


	public function deleteSearch( int $id ): bool {
		$search = Search::find($id);
		if ( $search === null ) {
			return false;
		}
		$search->delete();

		return true;
	}

	public function enforceRetention( string $max_age = '1 year', int $max_searches = 1000000 ): void {
		/**
		 * @var wpdb $wpdb;
		 */
		global $wpdb;

		$allowed_max_age = array( '1 year', '2 year', '1 month', '3 month', '6 month', '1 week', '2 week' );
		if ( !in_array($max_age, $allowed_max_age, true ) ) {
			$max_age = '1 year';
		}

		// phpcs:disable
		$wpdb->query(
			'DELETE FROM ' . Search::getTableName() . ' WHERE date < DATE_SUB(NOW(), INTERVAL ' . $max_age . ')'
		);

		$count = $wpdb->get_var('SELECT COUNT(*) FROM ' . Search::getTableName());
		if ($count > $max_searches) {
			$excess = $count - $max_searches;
			$wpdb->query(
				$wpdb->prepare(
					'DELETE FROM ' . Search::getTableName() . ' ORDER BY date ASC LIMIT %d',
					$excess
				)
			);
		}
		// phpcs:enable
	}

	/**
	 * Deletes all data from the tables with an auto-increment reset
	 *
	 * @return void
	 */
	public function reset(): void {
		Search::truncateTable();
	}
}
