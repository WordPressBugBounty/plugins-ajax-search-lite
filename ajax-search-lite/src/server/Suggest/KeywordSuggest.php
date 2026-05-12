<?php
namespace WPDRMS\ASL\Suggest;

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

class KeywordSuggest extends AbstractSuggest {

	private AbstractSuggest $suggest;

	public function __construct( string $source, $args ) {
		$args['taxonomy'] = $source === 'tags' ? 'post_tag' : $args['taxonomy'];
		$source           = $source === 'tags' ? 'terms' : $source;
		$args             = apply_filters('asl/suggestions/args', $args, $source);

		switch ( $source ) {
			case 'google':
			default:
				$this->suggest = new SuggestGoogle($args);
		}
	}

	public function getKeywords( string $q ): array {
		return apply_filters('asl/suggestions/keywords', $this->suggest->getKeywords($q), $q);
	}
}
