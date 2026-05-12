<?php

namespace WPDRMS\ASL\Cache\Queries;

use WPDRMS\PluginCore\Traits\SingletonTrait;
use WPDRMS\ASL\Statistics\ORM\Search;
use WPDRMS\ASL\Statistics\Queries\SearchQuery;

class SearchStatisticsQuery {
	use SingletonTrait;

	public function getRealTimeSearchStatistics(): array {
		return array(
			'all_time'         => array(
				'hits'   => $this->getCacheHits(),
				'misses' => $this->getCacheMisses(),
			),
			'last_24_hours'    => array(
				'hits'   => $this->getCacheHits(true),
				'misses' => $this->getCacheMisses(true),
			),
			'last_20_searches' => $this->getLastCachedSearches(),
		);
	}


	public function getCacheHits( bool $past_24_hours = false ): int {
		global $wpdb;

		$interval_condition = $past_24_hours ? ' AND date >= DATE_SUB(NOW(), INTERVAL 1 DAY)' : '';
		return intval(
			$wpdb->get_var( // phpcs:ignore
				'SELECT COUNT(*) FROM ' . Search::getTableName() . ' WHERE cached=1' . $interval_condition // phpcs:ignore
			)
		);
	}

	public function getCacheMisses( bool $past_24_hours = false ): int {
		global $wpdb;

		$interval_condition = $past_24_hours ? ' AND date >= DATE_SUB(NOW(), INTERVAL 1 DAY)' : '';
		return intval(
			$wpdb->get_var( // phpcs:ignore
				'SELECT COUNT(*) FROM ' . Search::getTableName() . ' WHERE cached=0' . $interval_condition // phpcs:ignore
			)
		);
	}

	public function getLastCachedSearches( int $count = 20 ): array {
		return SearchQuery::instance()->getSearches(
			array(
				'variation' => 'latest',
				'cached'    => 1,
				'limit'     => $count,
			) 
		)['results'];
	}
}
