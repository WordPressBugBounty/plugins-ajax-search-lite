<?php

namespace WPDRMS\AdminUI\Options;

use JsonSerializable;

class TaxonomyExclusionRule implements JsonSerializable {
	public string $id;
	public string $taxonomy;

	/** @var int[] */
	public array $terms;

	public function __construct( array $data ) {
		$this->id       = (string) ( $data['id'] ?? '' );
		$this->taxonomy = sanitize_key( (string) ( $data['taxonomy'] ?? '' ) );
		$this->terms    = array_map( 'intval', (array) ( $data['terms'] ?? array() ) );
	}

	/** @return array{id: string, taxonomy: string, terms: int[]} */
	public function jsonSerialize(): array {
		return array(
			'id'       => $this->id,
			'taxonomy' => $this->taxonomy,
			'terms'    => $this->terms,
		);
	}
}
