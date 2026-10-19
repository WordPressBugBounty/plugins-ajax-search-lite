<?php

namespace WPDRMS\ASL\Statistics;

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

/**
 * The statistics tables are shared by every site of a network (network prefix, a blog_id column). On a
 * network, an administrator of one site reads that site's rows only; a network administrator reads any
 * site's, with the blog_id filter as they set it. On a single site nothing is limited.
 *
 * Applied in the queries, so every reader (REST routes, CSV exports, the realtime views) follows it.
 */
class BlogScope {
	/**
	 * The blog the current user's reads are limited to, null for no limit
	 */
	public static function limit(): ?int {
		if ( is_multisite() && !current_user_can('manage_network_options') ) {
			return get_current_blog_id();
		}
		return null;
	}

	/**
	 * Query arguments limited to the current user's blog
	 *
	 * @param array<string, mixed> $args
	 * @return array<string, mixed>
	 */
	public static function args( array $args ): array {
		$blog_id = self::limit();
		if ( $blog_id !== null ) {
			$args['blog_id'] = $blog_id;
		}
		return $args;
	}

	/**
	 * A prepared SQL condition on the blog_id column, '1=1' when not limited
	 *
	 * @param string $column
	 */
	public static function condition( string $column = 'blog_id' ): string {
		global $wpdb;
		$blog_id = self::limit();
		if ( $blog_id === null ) {
			return '1=1';
		}
		return $wpdb->prepare( $column . ' = %d', $blog_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}
