<?php
/**
 * JXM Collective theme functions.
 *
 * @package JXM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'JXM_VERSION', '1.0.0' );

/**
 * Theme asset URL helper.
 *
 * @param string $path Path relative to the assets directory.
 * @return string
 */
function jxm_asset( $path ) {
	return get_template_directory_uri() . '/assets/' . ltrim( $path, '/' );
}

/**
 * Theme setup.
 */
function jxm_setup() {
	load_theme_textdomain( 'jxm', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'custom-logo', array(
		'height'      => 80,
		'width'       => 240,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'jxm' ),
			'footer'  => __( 'Footer Menu', 'jxm' ),
		)
	);

	add_image_size( 'jxm-card', 800, 520, true );
}
add_action( 'after_setup_theme', 'jxm_setup' );

/**
 * Register widget areas.
 */
function jxm_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Sidebar', 'jxm' ),
			'id'            => 'sidebar-1',
			'description'   => __( 'Add widgets here.', 'jxm' ),
			'before_widget' => '<section id="%1$s" class="widget mb-8 %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="font-serif text-xl mb-4 text-jxm-navy">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'jxm_widgets_init' );

/**
 * Enqueue compiled CSS and JS.
 */
function jxm_enqueue_assets() {
	$css_path = get_template_directory() . '/assets/css/main.min.css';
	$js_path  = get_template_directory() . '/assets/js/main.min.js';

	wp_enqueue_style(
		'jxm-google-fonts',
		'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Outfit:wght@300;400;500;600;700&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'jxm-style',
		jxm_asset( 'css/main.min.css' ),
		array( 'jxm-google-fonts' ),
		file_exists( $css_path ) ? filemtime( $css_path ) : JXM_VERSION
	);

	wp_enqueue_script( 'jquery' );

	wp_enqueue_script(
		'jxm-scripts',
		jxm_asset( 'js/main.min.js' ),
		array( 'jquery' ),
		file_exists( $js_path ) ? filemtime( $js_path ) : JXM_VERSION,
		true
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'jxm_enqueue_assets' );

/**
 * Add Tailwind classes to primary nav links.
 *
 * @param array    $atts HTML attributes.
 * @param WP_Post  $item Menu item.
 * @param stdClass $args Menu args.
 * @return array
 */
function jxm_nav_link_attributes( $atts, $item, $args ) {
	if ( isset( $args->theme_location ) && 'primary' === $args->theme_location ) {
		$atts['class'] = 'text-sm font-medium tracking-wide text-white/90 hover:text-jxm-gold transition-colors duration-300';
	}

	if ( isset( $args->theme_location ) && 'footer' === $args->theme_location ) {
		$atts['class'] = 'text-sm text-white/70 hover:text-jxm-gold transition-colors duration-300';
	}

	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'jxm_nav_link_attributes', 10, 3 );

/**
 * Add classes to primary nav list items.
 *
 * @param array    $classes Menu item classes.
 * @param WP_Post  $item    Menu item.
 * @param stdClass $args    Menu args.
 * @return array
 */
function jxm_nav_css_class( $classes, $item, $args ) {
	if ( isset( $args->theme_location ) && in_array( $args->theme_location, array( 'primary', 'footer' ), true ) ) {
		$classes[] = 'list-none';
	}

	return $classes;
}
add_filter( 'nav_menu_css_class', 'jxm_nav_css_class', 10, 3 );

/**
 * Fallback menu when no menu is assigned to the primary location.
 */
function jxm_fallback_menu() {
	echo '<ul class="flex flex-col lg:flex-row lg:items-center gap-4 lg:gap-8">';
	echo '<li><a class="text-sm font-medium tracking-wide text-white/90 hover:text-jxm-gold transition-colors duration-300" href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'jxm' ) . '</a></li>';
	echo '</ul>';
}
