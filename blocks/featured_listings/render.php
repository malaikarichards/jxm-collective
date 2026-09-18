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
$listings = get_field( 'listings' );

if ( $listings && ! is_array( $listings ) ) {
	$listings = array( $listings );
}

$listings = is_array( $listings ) ? $listings : array();

$classes = array( 'jxm-featured-listings', 'py-20', 'lg:py-24' );

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

					<div class="btn-arrow shrink-0">
						<a
							class="text-primary font-semibold no-underline"
							href="<?php echo esc_url( $button_url ); ?>"
							<?php echo $button_target ? 'target="' . esc_attr( $button_target ) . '" rel="noopener noreferrer"' : ''; ?>
						>
							<?php echo esc_html( $button_title ); ?>
						</a>
					</div>

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
		<?php else : ?>
			<p class="text-jxm-navy/70"><?php esc_html_e( 'Select listings in the block settings to display them here.', 'jxm' ); ?></p>
		<?php endif; ?>
	</div>
</section>
