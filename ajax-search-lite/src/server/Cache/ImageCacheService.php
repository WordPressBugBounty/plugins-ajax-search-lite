<?php

namespace WPDRMS\ASL\Cache;
\defined( 'ABSPATH' ) || exit; \defined( 'ASL_SCOPED' ) || ! \defined( 'ASL_PATH' ) || require_once ASL_PATH . 'vendor-prefixed/autoload.php';

use AjaxSearchLite\Vendor\WPDRMS\PluginCore\Traits\SingletonTrait;
use AjaxSearchLite\Vendor\WPDRMS\Utils\FileManager;
use AjaxSearchLite\Vendor\WPDRMS\Utils\BFI_Thumb_1_3;

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
