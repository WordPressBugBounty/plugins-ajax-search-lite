<?php

namespace WPDRMS\ASL\Cache\ORM;

use WPDRMS\PluginCore\ORM\Model;

class CacheData extends Model {
	protected static string $table_name = 'asp_cache';
	protected static $primary_key       = 'hash';

	protected static array $columns = array(
		'hash'                 => 'VARCHAR(14) NOT NULL',
		'created_at'           => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
		'content'              => 'MEDIUMTEXT',
		'INDEX idx_created_at' => '(created_at)',
		'PRIMARY KEY'          => '(hash)',
	);

	public string $hash        = '';
	public ?string $created_at = null;
	public string $content     = '';

	public function createdAtTimestamp(): int {
		return strtotime($this->created_at);
	}

	/**
	 * @return string[]
	 */
	public static function listHashes(): array {
		global $wpdb;
		return $wpdb->get_col( 'SELECT hash FROM ' . self::getTableName() ); // phpcs:ignore
	}

	public static function count(): int {
		global $wpdb;
		return $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::getTableName() ); // phpcs:ignore
	}

	public static function olderThanCount( int $ttl_seconds ): int {
		global $wpdb;
		return $wpdb->get_var( // phpcs:ignore
			$wpdb->prepare(
				'SELECT count(*) FROM ' . self::getTableName() . ' WHERE created_at < %s', // phpcs:ignore
				date( 'Y-m-d H:i:s', time() - $ttl_seconds )
			)
		);
	}

	/**
	 * @param int $ttl_seconds
	 * @return string[]
	 */
	public static function listOlderThanHashes( int $ttl_seconds ): array {
		global $wpdb;
		return $wpdb->get_col( // phpcs:ignore
			$wpdb->prepare(
				'SELECT hash FROM ' . self::getTableName() . ' WHERE created_at < %s', // phpcs:ignore
				date( 'Y-m-d H:i:s', time() - $ttl_seconds )
			)
		);
	}

	public static function deleteOlderThan( int $ttl_seconds ): int {
		global $wpdb;
		$old_count = self::olderThanCount($ttl_seconds);
		$wpdb->query( // phpcs:ignore
			$wpdb->prepare(
				'DELETE FROM ' . self::getTableName() . ' WHERE created_at < %s', // phpcs:ignore
				date( 'Y-m-d H:i:s', time() - $ttl_seconds )
			)
		);
		return $old_count;
	}
}
