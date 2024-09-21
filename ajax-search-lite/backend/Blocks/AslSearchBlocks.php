<?php

namespace WPDRMS\Backend\Blocks;

use WD_ASL_Styles;

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

/**
 * Full Site Editor and Gutenberg blocks controller
 */
class AslSearchBlocks {

	/**
	 * Media query
	 *
	 * @var string
	 */
	private static string $media_query = '';

	public function __construct() {
		add_action('init', array( $this, 'register' ));
	}

	/**
	 * Server side registration of the blocks
	 *
	 * @hook init
	 * @return void
	 */
	public function register(): void {
		if ( !function_exists('register_block_type') ) {
			return;
		}
		wp_register_script(
			'wd-asl-gutenberg',
			ASL_URL_NP . 'backend/Blocks/assets/search-block.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-server-side-render' ),
			ASL_CURR_VER_STRING
		);
		wp_register_style(
			'wd-asl-gutenberg-css',
			ASL_URL_NP . 'backend/Blocks/assets/search-block.css',
			array( 'wp-edit-blocks' ),
			ASL_CURR_VER_STRING
		);
		register_block_type(
			'ajax-search-lite/block-asl-main',
			array(
				'editor_script'   => 'wd-asl-gutenberg',
				'editor_style'    => 'wd-asl-gutenberg-css',
				'render_callback' => array( $this, 'render' )
			),

		);
	}

	/**
	 * How to render the ajax-search-lite/block-asl-main block via ServerSideRender JSX component
	 *
	 * @return string
	 */
	public function render(): string {
		// Editor render
		if ( isset($_GET['context']) && $_GET['context'] === 'edit' ) {
			return do_shortcode('[wd_asl include_styles=1]');

		}
		return do_shortcode("[wd_asl]");
	}
}
