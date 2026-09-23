<?php
/**
 * Listing archive filters and helpers.
 *
 * @package JXM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize and return current listing filter values from the request.
 *
 * @return array{keyword: string, beds: int, baths: float}
 */
function jxm_get_listing_filters() {
	$keyword = isset( $_GET['keyword'] ) ? sanitize_text_field( wp_unslash( $_GET['keyword'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$beds    = isset( $_GET['beds'] ) ? absint( $_GET['beds'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$baths   = isset( $_GET['baths'] ) ? (float) wp_unslash( $_GET['baths'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( $baths < 0 ) {
		$baths = 0;
	}

	return array(
		'keyword' => $keyword,
		'beds'    => $beds,
		'baths'   => $baths,
	);
}

/**
 * Apply keyword, beds, baths filters and 12-per-page to the listing archive.
 *
 * @param WP_Query $query Main query.
 */
function jxm_listing_archive_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'listing' ) ) {
		return;
	}

	$query->set( 'posts_per_page', 12 );

	$filters = jxm_get_listing_filters();

	// Use a custom flag instead of `s` so WP does not treat this as is_search()
	// (which would load search.php instead of archive-listing.php).
	if ( $filters['keyword'] ) {
		$query->set( 'jxm_listing_keyword', $filters['keyword'] );
	}

	$meta_query = array( 'relation' => 'AND' );

	if ( $filters['beds'] > 0 ) {
		$meta_query[] = array(
			'key'     => 'bedrooms',
			'value'   => $filters['beds'],
			'type'    => 'NUMERIC',
			'compare' => '>=',
		);
	}

	if ( $filters['baths'] > 0 ) {
		$meta_query[] = array(
			'key'     => 'bathrooms',
			'value'   => $filters['baths'],
			'type'    => 'NUMERIC',
			'compare' => '>=',
		);
	}

	if ( count( $meta_query ) > 1 ) {
		$query->set( 'meta_query', $meta_query );
	}
}
add_action( 'pre_get_posts', 'jxm_listing_archive_query' );

/**
 * Keyword search across title, content, address, and MLS #.
 *
 * @param string   $where SQL WHERE clause.
 * @param WP_Query $query Query.
 * @return string
 */
function jxm_listing_keyword_where( $where, $query ) {
	global $wpdb;

	$keyword = $query->get( 'jxm_listing_keyword' );

	if ( ! $keyword || is_admin() ) {
		return $where;
	}

	$like = '%' . $wpdb->esc_like( $keyword ) . '%';

	$where .= $wpdb->prepare(
		" AND (
			{$wpdb->posts}.post_title LIKE %s
			OR {$wpdb->posts}.post_content LIKE %s
			OR {$wpdb->posts}.post_excerpt LIKE %s
			OR EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} pm
				WHERE pm.post_id = {$wpdb->posts}.ID
				AND pm.meta_key IN ( 'address', 'mls_id' )
				AND pm.meta_value LIKE %s
			)
		)",
		$like,
		$like,
		$like,
		$like
	);

	return $where;
}
add_filter( 'posts_where', 'jxm_listing_keyword_where', 10, 2 );

/**
 * Format a listing price for display.
 *
 * @param mixed $price Raw price value.
 * @return string
 */
function jxm_format_listing_price( $price ) {
	if ( '' === $price || null === $price ) {
		return '';
	}

	if ( is_numeric( $price ) ) {
		return '$' . number_format( (float) $price );
	}

	return (string) $price;
}

/**
 * Normalize features textarea into a list of strings.
 *
 * @param mixed $features Raw features value.
 * @return string[]
 */
function jxm_parse_listing_features( $features ) {
	if ( empty( $features ) ) {
		return array();
	}

	if ( is_array( $features ) ) {
		return array_values( array_filter( array_map( 'strval', $features ) ) );
	}

	$lines = preg_split( '/\r\n|\r|\n|,/', (string) $features );

	if ( ! is_array( $lines ) ) {
		return array();
	}

	return array_values(
		array_filter(
			array_map(
				static function ( $line ) {
					return trim( (string) $line );
				},
				$lines
			)
		)
	);
}
