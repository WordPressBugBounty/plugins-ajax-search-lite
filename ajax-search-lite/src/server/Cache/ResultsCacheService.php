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
				$new_response = array_merge(
					$response,
					array(
						'created' => time(),
					)
				);
				$file_name    = substr(md5($id . $s . wp_json_encode($options) . $autop . $call_num), 0, 14);
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
						$this->cache->set($file_name, $text);
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
				$file_name = substr(md5($id . $s . wp_json_encode($options) . $autop . $call_num), 0, 14);
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
	 * @return string[]
	 */
	public function keySet(): array {
		return $this->cache->keySet();
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
