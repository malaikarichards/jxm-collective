	<footer class="bg-jxm-navy text-white mt-auto">
		<div class="container mx-auto px-5 py-12 lg:py-16">
			<div class="flex flex-col lg:flex-row lg:justify-between gap-10">
				<div>
					<div class="flex items-center gap-4 mb-8">
						<img
							src="<?php echo esc_url( jxm_asset( 'images/the-luxury-home-team-logo-white.png' ) ); ?>"
							
							alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
							class="h-16 w-auto border-r border-white pr-4"
						>
						<img 
						    src="<?php echo esc_url( jxm_asset( 'images/jxm-collective-logo-white.svg' ) ); ?>"
							alt="<?php esc_attr_e( 'The Luxury Home Team', 'jxm' ); ?>"
							class="h-16 w-auto"
						>
					</div>
					<p class="text-white text-sm max-w-md">
					South Florida's premier luxury real estate team, guiding discerning clients through exceptional properties.
					</p>
				</div>

				<div class="">
					<p class="text-white font-bold uppercase tracking-widest">
						Quick Links
</p>
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'footer',
							'container'      => false,
							'menu_class'     => '',
							'fallback_cb'    => false,
							'depth'          => 1,
						)
					);
					?>
				</div>
				<div class="">
					<p class="text-white font-bold uppercase tracking-widest">
						Connect
</p>
					<ul class="flex items-center gap-4 my-6">
						<li class="block"><a href="#" class="text-white bg-supporting rounded-full p-2"><i class="fa-brands fa-instagram"></i></a></li>
						<li class="block"><a href="#" class="text-white bg-supporting rounded-full p-2"><i class="fa-brands fa-facebook-f"></i></a></li>
					</ul>
					<p class="text-white font-bold uppercase tracking-widest">
						Stay in the Loop
					</p>
					<form action="#" class="flex items-center gap-2">
						<input type="email" placeholder="Email" class="text-white bg-transparent border border-white rounded-md p-2">
						<button type="submit" class="btn-primary"><i class="fa-solid fa-envelope"></i></button>
					</form>
				</div>
			</div>

			<div class="mt-10 pt-6 border-t border-white text-sm text-center lg:text-left text-white flex flex-col lg:flex-row justify-between">
				<p class="mb-4 lg:mb-0">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'jxm' ); ?></p>
				<ul class="flex items-center gap-4 flex-col lg:flex-row">
					<li><a href="#" class="text-white">Privacy Policy</a></li>
					<li><a href="#" class="text-white">Terms of Use</a></li>
					<li><a href="#" class="text-white"><i class="fa-solid fa-house text-accent"></i> Equal Housing Opportunity</a></li>
				</ul>
			</div>
		</div>
	</footer>

	<?php wp_footer(); ?>
</body>
</html>
