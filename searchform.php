<?php
/**
 * Search form.
 *
 * @package JXM
 */
?>
<form role="search" method="get" class="flex gap-2" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="sr-only" for="s"><?php esc_html_e( 'Search', 'jxm' ); ?></label>
	<input
		type="search"
		id="s"
		name="s"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="<?php esc_attr_e( 'Search…', 'jxm' ); ?>"
		class="flex-1 border border-jxm-navy/20 px-4 py-2 bg-white text-jxm-navy focus:outline-none focus:border-jxm-gold"
	>
	<button type="submit" class="bg-jxm-navy text-white px-4 py-2 text-sm hover:bg-jxm-gold transition-colors duration-300">
		<?php esc_html_e( 'Search', 'jxm' ); ?>
	</button>
</form>
