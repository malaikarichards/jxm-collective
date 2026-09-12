<?php
/**
 * No results template part.
 *
 * @package JXM
 */
?>
<section class="max-w-xl mx-auto text-center py-16">
	<h1 class="font-serif text-3xl text-jxm-navy mb-4"><?php esc_html_e( 'Nothing found', 'jxm' ); ?></h1>
	<p class="text-jxm-navy/70 mb-8"><?php esc_html_e( 'It looks like nothing was found at this location. Try a search?', 'jxm' ); ?></p>
	<?php get_search_form(); ?>
</section>
