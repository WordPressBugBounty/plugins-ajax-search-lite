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
	 * @return bool
	 */
	public function set( string $key, string $content ): bool;

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
	 * @return string[]
	 */
	public function keySet(): array;
}
