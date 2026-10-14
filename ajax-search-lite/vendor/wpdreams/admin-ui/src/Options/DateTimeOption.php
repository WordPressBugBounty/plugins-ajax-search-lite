<?php

namespace WPDRMS\AdminUI\Options;

use DateTimeImmutable;

/**
 * A moment in time (1.15.0): a UTC Unix timestamp, 0 when unset. Anything that is not an int or a digit-only string,
 * and any negative number, reads as 0.
 *
 * toLocal() and fromLocal() convert it to and from the `Y-m-d\TH:i` wall clock of the site's timezone (wp_timezone()):
 * what an <input type="datetime-local"> holds (DateTimeInput), so a plugin never converts by hand.
 */
class DateTimeOption extends AbstractOption {
	protected array $defaults = array(
		'value' => 0,
	);

	public int $value;

	public function __construct( array $args ) {
		parent::__construct($args);
		$value       = $this->args['value'];
		$this->value = is_int($value) || ( is_string($value) && ctype_digit($value) ) ? max(0, (int) $value) : 0;
	}

	/** The site-timezone `Y-m-d\TH:i` of a timestamp; '' for 0 (unset) or less. */
	public static function toLocal( int $timestamp ): string {
		if ( $timestamp <= 0 ) {
			return '';
		}
		return ( new DateTimeImmutable('@' . $timestamp) )->setTimezone(wp_timezone())->format('Y-m-d\TH:i');
	}

	/**
	 * A `Y-m-d\TH:i` (or `Y-m-d H:i`) wall clock in the site's timezone as a timestamp; 0 for '' and for anything
	 * else that is not exactly such a date, an impossible one (2026-02-30) included.
	 */
	public static function fromLocal( string $local ): int {
		$local = trim($local);
		if ( $local === '' ) {
			return 0;
		}
		foreach ( array( '!Y-m-d\TH:i', '!Y-m-d H:i' ) as $format ) {
			$date = DateTimeImmutable::createFromFormat($format, $local, wp_timezone());
			// PHP 8.2+ answers false when there was nothing to report; 7.4 to 8.1 answer an array of counts.
			$errors = DateTimeImmutable::getLastErrors();
			$clean  = false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] );
			if ( false !== $date && $clean ) {
				return max(0, $date->getTimestamp());
			}
		}
		return 0;
	}
}
