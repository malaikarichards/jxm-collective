<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'min-h-screen flex flex-col text-jxm-black antialiased font-sans' ); ?>>
<?php wp_body_open(); ?>

<header class="sticky top-0 z-50 bg-white shadow">
	<div class="container mx-auto">
		<nav class="relative flex justify-between items-center py-3">
			<div class="brand">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="block">
						<img
							src="<?php echo esc_url( jxm_asset( 'images/tlht-jxmc-horizontal-logo-navy.svg' ) ); ?>"
							alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
							class="h-8 w-auto object-contain pr-8"
						>
					</a>
				<?php endif; ?>
			</div>

			<button
				type="button"
				class="menu-toggle lg:hidden inline-flex items-center justify-center w-10 h-10 text-black"
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

			<div id="primary-menu" class="flex items-center justify-between hidden absolute lg:flex lg:static left-0 right-0 top-full bg-white lg:bg-transparent px-5 lg:px-0 pb-6 lg:pb-0 flex-col lg:flex-row">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'flex flex-col lg:flex-row lg:items-center gap-4 lg:gap-9',
						'fallback_cb'    => 'jxm_fallback_menu',
					)
				);
				?>
				<a class="btn-primary bg-accent text-white text-center font-semibold py-3 px-6 lg:ml-9 mt-9 lg:mt-0 " href="/contact/">Schedule a Consultation</a>
			</div>
		</nav>
	</div>
</header>
