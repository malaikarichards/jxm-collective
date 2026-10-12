<?php
/**
 * BeachesMLS IDX disclaimer required on pages displaying IDX listings (BeachesMLS rule 20.3.7).
 *
 * @package JXM
 *
 * @var array $args {
 *     @type string $class Optional wrapper classes.
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="flex flex-col gap-4 sm:flex-row sm:items-start text-sm text-jxm-navy/70 <?php echo esc_attr( $args['class'] ?? '' ); ?>">
	<img
		src="<?php echo esc_url( jxm_beachesmls_logo_url() ); ?>"
		alt="<?php esc_attr_e( 'BeachesMLS', 'jxm' ); ?>"
		class="w-40 h-auto shrink-0 self-start sm:mt-1"
		width="160"
		height="28"
		loading="lazy"
		decoding="async"
	>
	<div>
		<p class="mb-2">
			<?php
			printf(
				/* translators: %s: current year */
				esc_html__( 'All listings featuring the BMLS logo are provided by BeachesMLS, Inc. This information is not verified for authenticity or accuracy and is not guaranteed. Copyright &copy;%s BeachesMLS, Inc.', 'jxm' ),
				esc_html( wp_date( 'Y' ) )
			);
			?>
		</p>
		<p class="mb-0">
			<?php esc_html_e( 'IDX information is provided exclusively for consumers\' personal, non-commercial use, may not be used for any purpose other than to identify prospective properties consumers may be interested in purchasing, and is deemed reliable but is not guaranteed accurate by BeachesMLS.', 'jxm' ); ?>
		</p>
	</div>
</div>
