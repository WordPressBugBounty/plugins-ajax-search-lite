<?php

namespace WPDRMS\AdminUI\Options;

interface Option {
	/**
	 * Returns all public properties from the option
	 *
	 * @return Array<string, mixed>
	 */
	public function value(): array;
}
