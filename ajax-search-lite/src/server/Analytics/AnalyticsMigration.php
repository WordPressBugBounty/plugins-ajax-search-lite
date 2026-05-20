<?php

namespace WPDRMS\ASL\Analytics;

use WPDRMS\AdminUI\Options\EventConfigItem;
use WPDRMS\ASL\Analytics\ORM\AnalyticsOptions;

/**
 * One-time data migrations for the Analytics subsystem.
 *
 * Pass A — asl_analytics (flat legacy option) → asp_analytics_options ORM shape (iter2→iter3 combined)
 * Pass B — asp_analytics_options iter2 shape (category/label/value keys) → iter3 params[] shape
 */
final class AnalyticsMigration {

	private const TRIGGERS = array(
		'focus',
		'search_start',
		'search_end',
		'magnifier',
		'return',
		'facet_change',
		'result_click',
	);

	private const DEFAULT_NAMES = array(
		'focus'        => 'Search Focus',
		'search_start' => 'Search Start',
		'search_end'   => 'Search End',
		'magnifier'    => 'Magnifier Click',
		'return'       => 'Return Press',
		'facet_change' => 'Facet Change',
		'result_click' => 'Result Click',
	);

	public static function run(): void {
		self::migrateFromLegacyFlat();
		self::migrateFromIter2Shape();
		self::migrateEmptyNames();
	}

	/**
	 * Pass A: migrate the old asl_analytics flat option → asp_analytics_options ORM.
	 * Only runs when the old option exists and the new one does not yet.
	 */
	private static function migrateFromLegacyFlat(): void {
		$old = get_site_option('asl_analytics', false);
		if ( $old === false || get_site_option('asp_analytics_options', false) !== false ) {
			return;
		}

		if ( !is_array($old) ) {
			$old = array();
		}

		$method = $old['analytics'] ?? '0';

		$new_args = array(
			'method'       => array( 'value' => $method === 'event' ),
			'tracking_id'  => array( 'value' => $old['analytics_tracking_id'] ?? '' ),
			'focus'        => array( 'items' => array( self::legacyItem($old, 'gtag_focus') ) ),
			'search_start' => array( 'items' => array( self::legacyItem($old, 'gtag_search_start') ) ),
			'search_end'   => array( 'items' => array( self::legacyItem($old, 'gtag_search_end') ) ),
			'magnifier'    => array( 'items' => array( self::legacyItem($old, 'gtag_magnifier') ) ),
			'return'       => array( 'items' => array( self::legacyItem($old, 'gtag_return') ) ),
			'facet_change' => array( 'items' => array( self::legacyItem($old, 'gtag_facet_change') ) ),
			'result_click' => array( 'items' => array( self::legacyItem($old, 'gtag_result_click') ) ),
		);

		AnalyticsOptions::instance()->setArgs($new_args, false)->save();
	}

	/**
	 * Pass B: migrate asp_analytics_options from iter2 shape (category/label/value keys) to
	 * iter3 params[] shape. Runs once per item that still has the old 'category' key.
	 */
	private static function migrateFromIter2Shape(): void {
		$existing = get_site_option('asp_analytics_options', false);
		if ( !is_array($existing) ) {
			return;
		}

		$dirty = false;
		foreach ( self::TRIGGERS as $trigger ) {
			foreach ( $existing[ $trigger ]['items'] ?? array() as &$item ) {
				if ( !isset($item['category']) ) {
					continue;
				}
				$item['params'] = array(
					array(
						'key'   => 'event_category',
						'value' => (string) ( $item['category'] ?? '' ),
					),
					array(
						'key'   => 'event_label',
						'value' => (string) ( $item['label'] ?? '' ),
					),
					array(
						'key'   => 'value',
						'value' => (string) ( $item['value'] ?? '' ),
					),
				);
				unset( $item['category'], $item['label'], $item['value'] );
				$dirty = true;
			}
			unset( $item );
		}

		if ( $dirty ) {
			update_site_option('asp_analytics_options', $existing);
			AnalyticsOptions::instance()->load();
		}
	}

	/**
	 * Build an iter3-shaped EventConfigItem from a legacy flat-option trigger prefix.
	 *
	 * @param array  $old    The full asl_analytics option array.
	 * @param string $prefix E.g. 'gtag_focus'.
	 * @return array
	 */
	private static function migrateEmptyNames(): void {
		$existing = get_site_option( 'asp_analytics_options', false );
		if ( !is_array( $existing ) ) {
			return;
		}

		$dirty = false;
		foreach ( self::TRIGGERS as $trigger ) {
			$default = self::DEFAULT_NAMES[ $trigger ] ?? '';
			if ( !isset( $existing[ $trigger ]['items'] ) ) {
				continue;
			}
			foreach ( $existing[ $trigger ]['items'] as &$item ) {
				if ( !empty( $item['name'] ) ) {
					continue;
				}
				$item['name'] = $default;
				$dirty        = true;
			}
			unset( $item );
		}

		if ( $dirty ) {
			update_site_option( 'asp_analytics_options', $existing );
			AnalyticsOptions::instance()->load();
		}
	}

	private static function legacyItem( array $old, string $prefix ): array {
		$trigger = substr( $prefix, 5 ); // strip 'gtag_'
		return ( new EventConfigItem(
			array(
				'active' => (bool) ( $old[ $prefix ] ?? false ),
				'name'   => self::DEFAULT_NAMES[ $trigger ] ?? '',
				'action' => (string) ( $old[ $prefix . '_action' ] ?? '' ),
				'params' => array(
					array(
						'key'   => 'event_category',
						'value' => (string) ( $old[ $prefix . '_ec' ] ?? '' ),
					),
					array(
						'key'   => 'event_label',
						'value' => (string) ( $old[ $prefix . '_el' ] ?? '{phrase}' ),
					),
					array(
						'key'   => 'value',
						'value' => (string) ( $old[ $prefix . '_value' ] ?? '0' ),
					),
				),
			)
		) )->jsonSerialize();
	}
}
