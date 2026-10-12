<?php
/**
 * Featured listings block template.
 *
 * @package JXM
 *
 * @var array $block The block settings and attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$random    = (bool) get_field( 'random_listings' );
$min_price = 750000;

if ( $random ) {
	$count = (int) get_field( 'random_count' );

	$listings = get_posts(
		array(
			'post_type'        => 'listing',
			'post_status'      => 'publish',
			'posts_per_page'   => $count > 0 ? min( $count, 12 ) : 6,
			'orderby'          => 'rand',
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'price',
					'value'   => $min_price,
					'type'    => 'NUMERIC',
					'compare' => '>=',
				),
			),
		)
	);
} else {
	$listings = get_field( 'listings' );

	if ( $listings && ! is_array( $listings ) ) {
		$listings = array( $listings );
	}
}

$listings = is_array( $listings ) ? $listings : array();

$classes = array( 'jxm-featured-listings' );

if ( ! empty( $block['className'] ) ) {
	$classes[] = $block['className'];
}

if ( ! empty( $block['align'] ) ) {
	$classes[] = 'align' . $block['align'];
}

$id = ! empty( $block['anchor'] ) ? $block['anchor'] : 'jxm-featured-listings-' . $block['id'];
?>
<section id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<div class="container mx-auto">
		<?php if ( $listings ) : ?>
			<div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
				<?php
				foreach ( $listings as $listing ) :
					$listing_id = $listing instanceof WP_Post ? $listing->ID : (int) $listing;

					if ( ! $listing_id ) {
						continue;
					}

					get_template_part(
						'template-parts/content',
						'listing-card',
						array(
							'listing_id' => $listing_id,
						)
					);
				endforeach;
				?>
			</div>
		<?php elseif ( $random ) : ?>
			<p class="text-jxm-navy/70">
				<?php
				printf(
					/* translators: %s: minimum listing price */
					esc_html__( 'No listings priced at %s or more are available right now.', 'jxm' ),
					esc_html( jxm_format_listing_price( $min_price ) )
				);
				?>
			</p>
		<?php else : ?>
			<p class="text-jxm-navy/70"><?php esc_html_e( 'Select listings in the block settings to display them here.', 'jxm' ); ?></p>
		<?php endif; ?>
	</div>
</section>
