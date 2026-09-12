<?php
/**
 * Comments template.
 *
 * @package JXM
 */

if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="comments-area mt-12 pt-8 border-t border-jxm-navy/10">
	<?php if ( have_comments() ) : ?>
		<h2 class="font-serif text-2xl text-jxm-navy mb-6">
			<?php
			$jxm_comment_count = get_comments_number();
			printf(
				esc_html( _n( '%s comment', '%s comments', $jxm_comment_count, 'jxm' ) ),
				esc_html( number_format_i18n( $jxm_comment_count ) )
			);
			?>
		</h2>

		<ol class="comment-list space-y-6">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
				)
			);
			?>
		</ol>

		<?php the_comments_navigation(); ?>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'title_reply' => __( 'Leave a comment', 'jxm' ),
			'class_form'  => 'space-y-4',
		)
	);
	?>
</div>
