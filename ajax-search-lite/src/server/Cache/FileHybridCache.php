<?php

namespace WPDRMS\ASL\Cache;

use Exception;
use WPDRMS\ASL\Cache\ORM\CacheData;
use WPDRMS\Utils\FileManager;

/**
 * Fast file hybrid cache
 *
 * Stores cached file names (not the data) in a database and files with the data on the filesystem
 * This ensures that the listNames() is fast for large caches.
 * Supports key lengths up to 14 characters and data up to 16MB.
 */
class FileHybridCache implements Cache {
	use CacheCommon;

	const EXTENSION = '.json';

	private string $path;
	private string $cache_namespace;
	private string $cache_path;
	private FileManager $file_manager;
	private int $ttl;

	public function __construct( string $path, string $cache_namespace, int $ttl = 3600 ) {
		$this->path            = FileManager::instance()->addDirectorySeparator($path);
		$this->cache_namespace = $cache_namespace;
		$this->file_manager    = FileManager::instance();
		$this->cache_path      = $this->file_manager->addDirectorySeparator($this->path . $this->cache_namespace);
		$this->ttl             = $ttl;
	}

	/**
	 * @param string $key Max 14 characters
	 * @return false|string
	 * @throws Exception
	 */
	public function get( string $key ) {
		$this->checkKey($key);
		$file_path = $this->cache_path . $key . self::EXTENSION;

		$row = CacheData::find($key);
		if ( $row === null ) {
			return false;
		}

		if ( $row->createdAtTimestamp() < time() - $this->ttl ) {
			$row->delete();
			$this->file_manager->delFile($this->cache_path . $key);
			return false;
		}
		if ( !$this->file_manager->isFile( $file_path ) ) {
			$row->delete();
			return false;
		}
		
		return $this->file_manager->read( $file_path );
	}

	/**
	 * @param string $key Max 14 characters
	 * @param string $content Max 16MB
	 * @return bool
	 * @throws Exception
	 */
	public function set( string $key, string $content ): bool {
		$this->checkKey($key);
		$file_path = $this->cache_path . $key . self::EXTENSION;

		if ( !$this->file_manager->isDir($this->cache_path) ) {
			$dir_exists = $this->file_manager->safeCreateDirectory($this->cache_path);
			CacheData::createTable();
			if ( !$dir_exists ) {
				return false;
			}
		}

		$this->delete($key);

		$is_written = $this->file_manager->write($file_path, $content);
		if ( !$is_written ) {
			return false;
		}

		CacheData::create(
			array(
				'hash' => $key,
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
			$row->delete();
		}
		return $this->file_manager->delFile($this->cache_path . $key . self::EXTENSION);
	}

	public function purge(): int {
		CacheData::truncateTable();
		return $this->file_manager->deleteFilesByPattern($this->cache_path, '*' . self::EXTENSION);
	}

	/**
	 * Return the list of cached files names from the database (fast)
	 *
	 * @return string[]
	 */
	public function keySet(): array {
		return CacheData::listHashes();
	}

	/**
	 * Returns the list of cached files from the filesystem (slow)
	 *
	 * @return string[]
	 */
	public function listCachedFiles(): array {
		return array_map(
			function ( $file ) {
				return basename($file);
			},
			$this->file_manager->listFiles($this->cache_path)
		);
	}

	public function create(): void {
		$this->file_manager->safeCreateDirectory( $this->cache_path );
		CacheData::createTable();
	}

	public function destroy(): void {
		$this->file_manager->rmdir( $this->cache_path, true, true );
		CacheData::dropTable();
	}

	public function count(): int {
		return CacheData::count();
	}

	public function clear(): int {
		$stale_hashes = CacheData::listOlderThanHashes($this->ttl);
		foreach ( $stale_hashes as $hash ) {
			$this->file_manager->delFile($this->cache_path . $hash . self::EXTENSION);
		}
		CacheData::deleteOlderThan($this->ttl);
		return count($stale_hashes);
	}
}
