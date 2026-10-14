<?php

namespace WPDRMS\ASL\Cache;

use Exception;
use WPDRMS\ASL\Cache\ORM\CacheOptions;
use WPDRMS\ASL\Cache\Queries\SearchStatisticsQuery;
use WPDRMS\PluginCore\Traits\SingletonTrait;

class ResultsCacheService {
	use SingletonTrait;

	const RESULTS_CACHE_DIR = 'results';

	public SearchStatisticsQuery $search_statistics_query;

	private CacheOptions $options;
	private Cache $cache;


	public function __construct() {
		$this->options                 = CacheOptions::instance();
		$this->search_statistics_query = SearchStatisticsQuery::instance();

		if ( $this->options->cache_type->value === 'database' ) {
			$this->cache = new DatabaseCache( $this->options->results_max_age->value );
		} else {
			$this->cache = new FileHybridCache(
				wd_asl()->cache_path,
				self::RESULTS_CACHE_DIR,
				$this->options->results_max_age->value
			);
		}
	}

	public function loadHooks(): void {
		if ( !$this->options->status->value ) {
			if ( wp_next_scheduled( 'asp/cache/retention' ) ) {
				wp_unschedule_event( wp_next_scheduled( 'asp/cache/retention' ), 'asp/cache/retention');
			}
			wp_clear_scheduled_hook('asp/cache/retention');
			return;
		}

		add_action(
			'asp/cache/retention',
			function () {
				$this->clear();
			}
		);
		if ( ! wp_next_scheduled( 'asp/cache/retention' ) ) {
			wp_schedule_event( time() + 60, 'hourly', 'asp/cache/retention' );
		}

		if ( $this->options->clear_on_save->value ) {
			add_action('save_post', array( $this, 'purge' ), 10, 0);
		}

		add_filter(
			'asp/ajax/search/response',
			function ( array $response, int $id, string $s, array $options, int $call_num, $autop ) {
				if ( !self::cacheable() ) {
					return $response;
				}
				$new_response = array_merge(
					$response,
					array(
						'created' => time(),
					)
				);
				$file_name    = self::cacheKey($id, $s, $options, $autop, $call_num);
				if ( defined('JSON_INVALID_UTF8_IGNORE') ) {
					$text = wp_json_encode(
						$new_response,
						JSON_INVALID_UTF8_IGNORE
					);
				} else {
					$text = wp_json_encode(
						$new_response
					);
				}
				if ( $text !== false ) {
					try {
						$this->cache->set($file_name, $text, self::scope());
					} catch ( Exception $e ) {
						return $response;
					}
				}
				return $response;
			},
			999,
			6
		);

		add_filter(
			'asp/ajax/search/custom_output',
			function ( string $output, int $id, string $s, array $options, int $call_num, $autop ) {
				if ( !self::cacheable() ) {
					return '';
				}
				$file_name = self::cacheKey($id, $s, $options, $autop, $call_num);
				try {
					$content = $this->cache->get($file_name);
					if ( $content !== false ) {
						return $content;
					}
				} catch ( Exception $e ) { // phpcs:ignore
				}

				return '';
			},
			999,
			6
		);
	}

	/**
	 * Whether this request may read and write the cache: the cached responses are served to every
	 * visitor, so never the ones that may depend on the user (private posts for logged-in users,
	 * unlocked password protected posts)
	 */
	public static function cacheable(): bool {
		$cacheable = true;
		if ( is_user_logged_in() ) {
			// Each logged-in user has their own entries (scope())
			$cacheable = CacheOptions::instance()->logged_in_users->value;
		}
		foreach ( array_keys($_COOKIE) as $name ) {
			if ( strpos( (string) $name, 'wp-postpass_') === 0 ) {
				$cacheable = false;
			}
		}

		/**
		 * Whether the current user's search responses may be cached and served from the cache
		 *
		 * @param bool $cacheable
		 */
		return (bool) apply_filters('asl/results_cache/cacheable', $cacheable);
	}

	/**
	 * The cache scope of the current user: '' for visitors, a keyed hash of the user for logged-in
	 * users, whose entries are their own. The super file cache serves the files to anyone who knows the
	 * name: no one else can compute it without the site's secret, and the keyset only lists it to them.
	 */
	public static function scope(): string {
		if ( !is_user_logged_in() ) {
			return '';
		}
		return substr(
			hash_hmac('sha256', 'asl-results-cache|' . get_current_blog_id() . '|' . get_current_user_id(), wp_salt('auth')),
			0,
			16
		);
	}

	/**
	 * The cache key of a search, per blog. The super file cache front-end builds the same key
	 * (src/client/plugin/core/actions/search.ts).
	 *
	 * @param int                  $id
	 * @param string               $s
	 * @param array<string, mixed> $options
	 * @param mixed                $autop
	 * @param int                  $call_num
	 * @return string
	 */
	public static function cacheKey( int $id, string $s, array $options, $autop, int $call_num ): string {
		$scope = self::scope();
		return substr(
			md5($id . $s . wp_json_encode($options) . $autop . $call_num . '|' . get_current_blog_id() . ( $scope !== '' ? '|' . $scope : '' )),
			0,
			14
		);
	}

	public function purge(): int {
		return $this->cache->purge();
	}

	public function delete( string $filename ): bool {
		try {
			return $this->cache->delete($filename);
		} catch ( Exception $e ) {
			return false;
		}
	}

	public function resultsCacheUrl(): string {
		return wd_asl()->cache_url . self::RESULTS_CACHE_DIR . '/';
	}

	/**
	 * The visitors' entries and the current user's own
	 *
	 * @return string[]
	 */
	public function keySet(): array {
		return $this->cache->keySet(self::scope());
	}

	public function create(): void {
		$this->cache->create();
	}

	public function destroy(): void {
		$this->cache->destroy();
	}

	public function count(): int {
		return $this->cache->count();
	}

	public function clear(): int {
		return $this->cache->clear();
	}
}
