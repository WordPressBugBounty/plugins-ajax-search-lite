<?php
namespace WPDRMS\ASL\Suggest;

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

abstract class AbstractSuggest {
	/**
	 * This should always return an array of keywords or an empty array
	 *
	 * @param string $q search keyword
	 * @return array keywords
	 */
	abstract public function getKeywords( string $q ): array;
}
