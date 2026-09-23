<?php
/**
 * Listing card template part.
 *
 * @package JXM
 *
 * @var array $args {
 *     @type int $listing_id Optional listing ID when not in the main loop.
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$listing_id = isset( $args['listing_id'] ) ? (int) $args['listing_id'] : get_the_ID();

if ( ! $listing_id ) {
	return;
}

$permalink = get_permalink( $listing_id );
$title     = get_the_title( $listing_id );
$excerpt   = get_the_excerpt( $listing_id );
$price    = get_field( 'price', $listing_id );
$beds     = get_field( 'bedrooms', $listing_id );
$baths    = get_field( 'bathrooms', $listing_id );
$sqft     = get_field( 'sqft', $listing_id );
$address  = get_field( 'address', $listing_id );
$price_display = function_exists( 'jxm_format_listing_price' ) ? jxm_format_listing_price( $price ) : $price;
?>
<article id="post-<?php echo esc_attr( (string) $listing_id ); ?>" class="property-card bg-white shadow-soft overflow-hidden flex flex-col h-full">
	<a href="<?php echo esc_url( $permalink ); ?>" class="block overflow-hidden no-underline">
		<?php if ( has_post_thumbnail( $listing_id ) ) : ?>
			<?php
			echo get_the_post_thumbnail(
				$listing_id,
				'jxm-card',
				array(
					'class' => 'property-img w-full h-64 object-cover',
					'alt'   => $title,
				)
			);
			?>
		<?php else : ?>
			<div class="property-img w-full h-64 bg-secondary"></div>
		<?php endif; ?>
	</a>

	<div class="p-6 flex flex-col flex-grow">
		<?php if ( $address ) : ?>
			<p class="text-accent text-xs font-semibold uppercase tracking-widest mb-2"><?php echo esc_html( $address ); ?></p>
		<?php endif; ?>

		<h3 class="font-serif text-jxm-navy text-2xl mt-0 mb-3">
			<a href="<?php echo esc_url( $permalink ); ?>" class="no-underline text-jxm-navy hover:text-jxm-gold transition-colors duration-300">
				<?php echo esc_html( $title ); ?>
			</a>
		</h3>

		<?php if ( $price_display ) : ?>
			<p class="text-jxm-navy font-semibold mb-3"><?php echo esc_html( $price_display ); ?></p>
		<?php endif; ?>

		<?php if ( $excerpt ) : ?>
			<p class="text-jxm-navy/70 text-sm leading-relaxed mb-4"><?php echo esc_html( wp_strip_all_tags( $excerpt ) ); ?></p>
		<?php endif; ?>

		<?php if ( $beds || $baths || $sqft ) : ?>
			<ul class="mt-auto flex flex-wrap gap-4 text-sm text-jxm-navy/80">
				<?php if ( $beds ) : ?>
					<li><?php echo esc_html( $beds ); ?> <?php esc_html_e( 'Beds', 'jxm' ); ?></li>
				<?php endif; ?>
				<?php if ( $baths ) : ?>
					<li><?php echo esc_html( $baths ); ?> <?php esc_html_e( 'Baths', 'jxm' ); ?></li>
				<?php endif; ?>
				<?php if ( $sqft ) : ?>
					<li><?php echo esc_html( $sqft ); ?> <?php esc_html_e( 'Sq Ft', 'jxm' ); ?></li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>
	</div>
</article>
