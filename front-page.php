<?php
/**
 * Front page template.
 *
 * @package JXM
 */

get_header();
?>

<main>
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			if ( get_the_content() ) :
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
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
