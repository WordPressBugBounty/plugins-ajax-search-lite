<?php

namespace WPDRMS\ASL\Cache;

use Exception;
use WPDRMS\ASL\Cache\ORM\CacheData;

/**
 * Database cache
 *
 * Stores the cached data in the database
 * This ensures that the listNames() is fast for large caches.
 * Supports key lengths up to 14 characters and data up to 16MB.
 */
class DatabaseCache implements Cache {
	use CacheCommon;

	private int $ttl;

	public function __construct( int $ttl = 3600 ) {
		$this->ttl = $ttl;
	}

	/**
	 * @param string $key Max 14 characters
	 * @return false|string
	 * @throws Exception
	 */
	public function get( string $key ) {
		$this->checkKey($key);

		$row = CacheData::find($key);
		if ( $row === null ) {
			return false;
		}
		if ( $row->createdAtTimestamp() < time() - $this->ttl ) {
			$row->delete();
			return false;
		}

		return $row->content;
	}

	/**
	 * @param string $key Max 14 characters
	 * @param string $content Max 16MB
	 * @return bool
	 * @throws Exception
	 */
	public function set( string $key, string $content ): bool {
		$this->checkKey($key);
		$this->delete($key);

		CacheData::create(
			array(
				'hash'    => $key,
				'content' => $content,
			)
		);
		return true;
	}

	/**
	 * @param string $key Max 14 characters
	 * @return bool
	 * @throws Exception
	 */
	public function delete( string $key ): bool {
		$this->checkKey($key);
		$row = CacheData::find($key);
		if ( $row ) {
			return $row->delete();
		}
		return false;
	}

	public function purge(): int {
		$count = CacheData::count();
		CacheData::truncateTable();
		return $count;
	}

	/**
	 * Return the list of cached files names from the database (fast)
	 *
	 * @return string[]
	 */
	public function keySet(): array {
		return CacheData::listHashes();
	}


	public function create(): void {
		CacheData::createTable();
	}

	public function destroy(): void {
		CacheData::dropTable();
	}

	public function count(): int {
		return CacheData::count();
	}

	public function clear(): int {
		return CacheData::deleteOlderThan($this->ttl);
	}
}
