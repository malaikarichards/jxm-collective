<?php
/**
 * Front page template.
 *
 * @package JXM
 */

get_header();
?>

<section class="bg-jxm-navy text-white">
	<div class="container mx-auto px-5 py-20 lg:py-32">
		<div class="max-w-3xl">
			<img
				src="<?php echo esc_url( jxm_asset( 'images/jxm-collective-tag-logo-black.svg' ) ); ?>"
				alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
				class="h-24 lg:h-32 w-auto mb-10 brightness-0 invert"
			>
			<p class="font-serif text-2xl lg:text-4xl font-light leading-snug text-white/90">
				<?php echo esc_html( get_bloginfo( 'description' ) ?: __( 'The Luxury Home Team', 'jxm' ) ); ?>
			</p>
		</div>
	</div>
</section>

<main class="container mx-auto px-5 py-12 lg:py-16">
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			if ( get_the_content() ) :
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'max-w-3xl mx-auto' ); ?>>
					<div class="entry-content">
						<?php the_content(); ?>
					</div>
				</article>
				<?php
			endif;
		endwhile;
	endif;
	?>
</main>

<?php
get_footer();
