<?php
/**
 * Single post template.
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
				<?php the_title( '<h1 class="font-serif text-4xl lg:text-5xl text-jxm-navy mb-4">', '</h1>' ); ?>
				<p class="text-sm text-jxm-navy/60">
					<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				</p>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="mb-8 overflow-hidden rounded-sm">
					<?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-auto' ) ); ?>
				</div>
			<?php endif; ?>

			<div class="entry-content">
				<?php the_content(); ?>
			</div>

			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		</article>
		<?php
	endwhile;
	?>
</main>

<?php
get_footer();
