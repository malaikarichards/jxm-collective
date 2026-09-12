<?php
/**
 * Post card template part.
 *
 * @package JXM
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'bg-white shadow-sm overflow-hidden' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a href="<?php the_permalink(); ?>" class="block overflow-hidden">
			<?php the_post_thumbnail( 'jxm-card', array( 'class' => 'w-full h-52 object-cover' ) ); ?>
		</a>
	<?php endif; ?>
	<div class="p-6">
		<h2 class="font-serif text-2xl text-jxm-navy mb-3">
			<a href="<?php the_permalink(); ?>" class="hover:text-jxm-gold transition-colors duration-300">
				<?php the_title(); ?>
			</a>
		</h2>
		<div class="text-jxm-navy/70 text-sm leading-relaxed">
			<?php the_excerpt(); ?>
		</div>
	</div>
</article>
