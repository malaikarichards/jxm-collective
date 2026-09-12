<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'min-h-screen flex flex-col bg-jxm-cream text-jxm-black antialiased font-sans' ); ?>>
<?php wp_body_open(); ?>

<header class="bg-jxm-navy shadow-sm sticky top-0 z-50">
	<div class="container mx-auto px-5">
		<nav class="relative flex justify-between items-center py-4 lg:py-5">
			<div class="brand max-w-[160px] lg:max-w-[200px]">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="block">
						<img
							src="<?php echo esc_url( jxm_asset( 'images/jxm-collective-logo-light.svg' ) ); ?>"
							alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
							class="w-full h-auto"
						>
					</a>
				<?php endif; ?>
			</div>

			<button
				type="button"
				class="menu-toggle lg:hidden inline-flex items-center justify-center w-10 h-10 text-white"
				aria-controls="primary-menu"
				aria-expanded="false"
			>
				<span class="sr-only"><?php esc_html_e( 'Toggle menu', 'jxm' ); ?></span>
				<svg class="icon-open w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"></path>
				</svg>
				<svg class="icon-close w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"></path>
				</svg>
			</button>

			<div id="primary-menu" class="hidden lg:block absolute lg:static left-0 right-0 top-full bg-jxm-navy lg:bg-transparent px-5 lg:px-0 pb-6 lg:pb-0">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'flex flex-col lg:flex-row lg:items-center gap-4 lg:gap-8',
						'fallback_cb'    => 'jxm_fallback_menu',
					)
				);
				?>
			</div>
		</nav>
	</div>
</header>
