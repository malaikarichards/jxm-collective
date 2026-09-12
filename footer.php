	<footer class="bg-jxm-navy text-white mt-auto">
		<div class="container mx-auto px-5 py-12 lg:py-16">
			<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-10">
				<div class="flex items-center gap-8">
					<img
						src="<?php echo esc_url( jxm_asset( 'images/jxm-collective-logo-light.svg' ) ); ?>"
						alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
						class="h-16 w-auto"
					>
					<img
						src="<?php echo esc_url( jxm_asset( 'images/the-luxury-home-team-logo-white.png' ) ); ?>"
						alt="<?php esc_attr_e( 'The Luxury Home Team', 'jxm' ); ?>"
						class="h-16 w-auto"
					>
				</div>

				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'flex flex-wrap gap-6',
						'fallback_cb'    => false,
						'depth'          => 1,
					)
				);
				?>
			</div>

			<div class="mt-10 pt-6 border-t border-white/20 text-sm text-white/60">
				<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'jxm' ); ?></p>
			</div>
		</div>
	</footer>

	<?php wp_footer(); ?>
</body>
</html>
