<?php
/**
 * ACF block registration.
 *
 * @package JXM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register a theme block category in the inserter.
 *
 * @param array $categories Existing block categories.
 * @return array
 */
function jxm_block_categories( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'jxm',
			'title' => __( 'JXM', 'jxm' ),
		)
	);

	return $categories;
}
add_filter( 'block_categories_all', 'jxm_block_categories' );

/**
 * Register every ACF block that has a block.json file in /blocks.
 */
function jxm_register_acf_blocks() {
	if ( ! function_exists( 'acf_register_block_type' ) ) {
		return;
	}

	$manifests = glob( get_template_directory() . '/blocks/*/block.json' );

	if ( empty( $manifests ) ) {
		return;
	}

	foreach ( $manifests as $manifest ) {
		register_block_type( dirname( $manifest ) );
	}
}
add_action( 'init', 'jxm_register_acf_blocks' );
