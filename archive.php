<?php
/**
 * Archive template.
 *
 * @package JXM
 */

get_header();
?>

<main class="container mx-auto px-5 py-12 lg:py-16">
	<header class="mb-10">
		<?php the_archive_title( '<h1 class="font-serif text-4xl text-jxm-navy">', '</h1>' ); ?>
		<?php the_archive_description( '<div class="mt-3 text-jxm-navy/70">', '</div>' ); ?>
	</header>

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
