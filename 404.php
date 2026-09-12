<?php
/**
 * 404 template.
 *
 * @package JXM
 */

get_header();
?>

<main class="container mx-auto px-5 py-24 text-center">
	<p class="font-serif text-jxm-gold text-sm tracking-[0.3em] uppercase mb-4"><?php esc_html_e( '404', 'jxm' ); ?></p>
	<h1 class="font-serif text-4xl lg:text-5xl text-jxm-navy mb-6"><?php esc_html_e( 'Page not found', 'jxm' ); ?></h1>
	<p class="text-jxm-navy/70 mb-8"><?php esc_html_e( 'The page you are looking for does not exist or has been moved.', 'jxm' ); ?></p>
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="inline-block bg-jxm-navy text-white px-6 py-3 text-sm tracking-wide hover:bg-jxm-gold transition-colors duration-300">
		<?php esc_html_e( 'Back to home', 'jxm' ); ?>
	</a>
</main>

<?php
get_footer();
