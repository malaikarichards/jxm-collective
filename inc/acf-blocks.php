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

/**
 * Register the `acf.fields` listed in each block.json as a field group shown on that block.
 *
 * Fields without a `key` get `field_{block}_{name}`, which conditional logic in block.json can reference.
 */
function jxm_register_acf_block_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	foreach ( glob( get_template_directory() . '/blocks/*/block.json' ) as $manifest ) {
		$metadata = wp_json_file_decode( $manifest, array( 'associative' => true ) );

		if ( empty( $metadata['name'] ) || empty( $metadata['acf']['fields'] ) ) {
			continue;
		}

		$slug   = str_replace( '-', '_', preg_replace( '/^acf\//', '', $metadata['name'] ) );
		$fields = array();

		foreach ( $metadata['acf']['fields'] as $field ) {
			$fields[] = $field + array( 'key' => 'field_' . $slug . '_' . $field['name'] );
		}

		acf_add_local_field_group(
			array(
				'key'      => 'group_block_' . $slug,
				'title'    => $metadata['title'] ?? $metadata['name'],
				'fields'   => $fields,
				'location' => array(
					array(
						array(
							'param'    => 'block',
							'operator' => '==',
							'value'    => $metadata['name'],
						),
					),
				),
			)
		);
	}
}
add_action( 'acf/init', 'jxm_register_acf_block_fields' );
