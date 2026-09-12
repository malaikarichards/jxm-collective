<?php
/**
 * Main template.
 *
 * @package JXM
 */

get_header();
?>

<main class="container mx-auto px-5 py-12 lg:py-16">
	<?php if ( have_posts() ) : ?>
		<div class="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content', get_post_type() );
			endwhile;
			?>
		</div>

		<div class="mt-12">
			<?php the_posts_pagination(); ?>
		</div>
	<?php else : ?>
		<?php get_template_part( 'template-parts/content', 'none' ); ?>
	<?php endif; ?>
</main>

<?php
get_footer();
