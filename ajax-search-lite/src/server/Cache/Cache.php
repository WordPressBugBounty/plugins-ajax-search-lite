<?php

namespace WPDRMS\ASL\Cache;

interface Cache {
	/**
	 * @param string $key
	 * @return false|string
	 */
	public function get( string $key );

	/**
	 * @param string $key
	 * @param string $content
	 * @param string $scope '' for the visitors' entries, a logged-in user's own scope otherwise
	 * @return bool
	 */
	public function set( string $key, string $content, string $scope = '' ): bool;

	public function delete( string $key ): bool;

	/**
	 * Purges all cache items and returns the number of items removed.
	 *
	 * @return int
	 */
	public function purge(): int;

	public function create(): void;

	public function destroy(): void;

	public function count(): int;

	/**
	 * Clears the stale cache and returns the number of items removed.
	 *
	 * @return int
	 */
	public function clear(): int;

	/**
	 * The visitors' entries and the given scope's own
	 *
	 * @param string $scope
	 * @return string[]
	 */
	public function keySet( string $scope = '' ): array;
}
