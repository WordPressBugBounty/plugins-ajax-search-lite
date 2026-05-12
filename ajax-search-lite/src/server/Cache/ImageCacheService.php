<?php

namespace WPDRMS\ASL\Cache;

use WPDRMS\PluginCore\Traits\SingletonTrait;
use WPDRMS\Utils\FileManager;
use WPDRMS\Utils\BFI_Thumb_1_3;

class ImageCacheService {
	use SingletonTrait;

	/**
	 * @param string                                                        $url
	 * @param array{width?: int, height?: int, crop?: bool, color?: string} $params
	 * @return string
	 */
	public function get( string $url, array $params ): string {
		$params = array_merge(
			array(
				'width'   => 120,
				'height'  => 120,
				'crop'    => true,
				'max_age' => 2592000, // 30 days
			),
			$params
		);
		return BFI_Thumb_1_3::thumb( $url, $params, true ); // @phpstan-ignore-line
	}

	public function purge(): int {
		return FileManager::instance()->deleteFilesByPattern( wd_asl()->bfi_path, '*.*' );
	}
}
