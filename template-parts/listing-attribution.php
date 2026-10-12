<?php
/**
 * BeachesMLS logo and listing firm attribution required on IDX listing displays (BeachesMLS rules 20.3.3 and 20.3.6).
 *
 * The firm text must stay in a readily visible color and no smaller than the median text size of the listing data around it.
 *
 * @package JXM
 *
 * @var array $args {
 *     @type int    $listing_id Listing post ID.
 *     @type string $class      Optional wrapper classes.
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$listing_id = isset( $args['listing_id'] ) ? (int) $args['listing_id'] : get_the_ID();

if ( ! jxm_is_mls_listing( $listing_id ) ) {
	return;
}

$office       = jxm_get_listing_office( $listing_id );
$phone_digits = preg_replace( '/[^\d+]/', '', $office['phone'] );
?>
<div class="flex items-center gap-3 <?php echo esc_attr( $args['class'] ?? 'text-jxm-navy/80' ); ?>">
	<img
		src="<?php echo esc_url( jxm_beachesmls_logo_url() ); ?>"
		alt="<?php esc_attr_e( 'BeachesMLS', 'jxm' ); ?>"
		class="w-28 h-auto shrink-0"
		width="112"
		height="20"
		loading="lazy"
		decoding="async"
	>
	<?php if ( '' !== $office['name'] ) : ?>
		<p class="mb-0">
			<?php esc_html_e( 'Listing courtesy of', 'jxm' ); ?>
			<strong class="text-jxm-navy font-semibold"><?php echo esc_html( $office['name'] ); ?></strong>
			<?php if ( $office['phone'] ) : ?>
				<br><a href="<?php echo esc_url( 'tel:' . $phone_digits ); ?>" class="text-jxm-navy underline hover:text-accent"><?php echo esc_html( $office['phone'] ); ?></a>
			<?php endif; ?>
			<?php if ( $office['email'] ) : ?>
				<br><a href="<?php echo esc_url( 'mailto:' . $office['email'] ); ?>" class="text-jxm-navy underline hover:text-accent"><?php echo esc_html( $office['email'] ); ?></a>
			<?php endif; ?>
		</p>
	<?php endif; ?>
</div>
