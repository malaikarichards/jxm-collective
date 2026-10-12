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

/**
 * Whether the Redirection plugin's classes are available.
 *
 * @return bool
 */
function jxm_redirection_available() {
	return class_exists( 'Red_Item' ) && class_exists( 'Red_Group' );
}

/**
 * ID of the Redirection group holding removed-listing redirects, created on first use.
 *
 * @return int 0 when the group can't be created.
 */
function jxm_removed_listing_group_id() {
	$group_id = (int) get_option( 'jxm_redirect_group_id', 0 );

	if ( $group_id && Red_Group::get( $group_id ) ) {
		return $group_id;
	}

	$group = Red_Group::create( 'Removed listings', 1 );

	if ( ! $group ) {
		return 0;
	}

	update_option( 'jxm_redirect_group_id', $group->get_id(), false );

	return $group->get_id();
}

/**
 * Site-relative public path of a listing (e.g. /listing/123-main-st/), even while it is a draft.
 *
 * @param WP_Post $post Listing post.
 * @return string
 */
function jxm_listing_public_path( $post ) {
	// Drafts get ?p=123 permalinks, so build the URL as if it were published.
	$published              = clone $post;
	$published->post_status = 'publish';

	return wp_make_link_relative( get_permalink( $published ) );
}

/**
 * Listings archive URL filtered to the listing's city with at least its beds and baths.
 *
 * @param int $post_id Listing post ID.
 * @return string
 */
function jxm_similar_listings_url( $post_id ) {
	$args  = array();
	$parts = array_map( 'trim', explode( ',', (string) get_post_meta( $post_id, 'address', true ) ) );

	if ( ! empty( $parts[1] ) ) {
		$args['keyword'] = $parts[1];
	}

	// Match the archive dropdowns: beds 1-5, baths 1-4 in half steps.
	$beds = min( 5, (int) floor( (float) get_post_meta( $post_id, 'bedrooms', true ) ) );
	if ( $beds > 0 ) {
		$args['beds'] = $beds;
	}

	$baths = min( 4, floor( (float) get_post_meta( $post_id, 'bathrooms', true ) * 2 ) / 2 );
	if ( $baths > 0 ) {
		$args['baths'] = $baths;
	}

	return add_query_arg( array_map( 'rawurlencode', $args ), get_post_type_archive_link( 'listing' ) );
}

/**
 * Delete this theme's removed-listing redirects for a path, leaving redirects in other groups alone.
 *
 * @param string $path     Site-relative path.
 * @param int    $group_id Removed-listings group ID.
 */
function jxm_delete_listing_redirects( $path, $group_id ) {
	foreach ( Red_Item::get_for_matched_url( $path ) as $item ) {
		if ( (int) $item->get_group_id() === $group_id ) {
			$item->delete();
		}
	}
}

/**
 * Redirect drafted listings to a similar search via Redirection, and drop the redirect when republished.
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       Post.
 */
function jxm_sync_listing_redirect( $new_status, $old_status, $post ) {
	if ( 'listing' !== $post->post_type || $new_status === $old_status || ! $post->post_name || ! jxm_redirection_available() ) {
		return;
	}

	$drafted   = 'publish' === $old_status && 'draft' === $new_status;
	$published = 'publish' === $new_status;

	if ( ! $drafted && ! $published ) {
		return;
	}

	$group_id = jxm_removed_listing_group_id();

	if ( ! $group_id ) {
		return;
	}

	$path = jxm_listing_public_path( $post );

	jxm_delete_listing_redirects( $path, $group_id );

	if ( $drafted ) {
		$result = Red_Item::create(
			array(
				'url'         => $path,
				'match_type'  => 'url',
				'action_type' => 'url',
				'action_code' => 301,
				'action_data' => array( 'url' => jxm_similar_listings_url( $post->ID ) ),
				'group_id'    => $group_id,
				'title'       => 'Removed listing: ' . get_the_title( $post ),
			)
		);

		if ( is_wp_error( $result ) ) {
			error_log( 'Listing redirect error for ' . $path . ': ' . $result->get_error_message() );
		}
	}
}
add_action( 'transition_post_status', 'jxm_sync_listing_redirect', 10, 3 );
