<?php
/**
 * Page template.
 *
 * @package JXM
 */

get_header();
?>

<main class="container mx-auto px-5 py-12 lg:py-16">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'max-w-3xl mx-auto' ); ?>>
			<header class="mb-8">
				<?php the_title( '<h1 class="font-serif text-4xl lg:text-5xl text-jxm-navy">', '</h1>' ); ?>
			</header>
			<div class="entry-content">
				<?php the_content(); ?>
			</div>
		</article>
		<?php
	endwhile;
	?>
</main>

<?php
get_footer();
