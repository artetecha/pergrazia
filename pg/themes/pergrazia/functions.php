<?php
/**
 * Pergrazia theme functions.
 *
 * @package Pergrazia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PERGRAZIA_VERSION', '1.3.0' );

/**
 * Register theme features and navigation areas.
 */
function pergrazia_setup(): void {
	load_theme_textdomain( 'pergrazia', get_template_directory() . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/editor.css' );

	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 120,
			'width'       => 420,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_theme_support(
		'custom-header',
		array(
			'default-image'      => get_template_directory_uri() . '/assets/images/pergrazia-hero.png',
			'width'              => 1280,
			'height'             => 444,
			'flex-width'         => true,
			'flex-height'        => true,
			'header-text'        => false,
			'uploads'            => true,
			'video'              => false,
		)
	);

	register_default_headers(
		array(
			'pergrazia-original' => array(
				'url'           => '%s/assets/images/pergrazia-hero.png',
				'thumbnail_url' => '%s/assets/images/pergrazia-hero.png',
				'description'   => __( 'Original Pergrazia artwork', 'pergrazia' ),
			),
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'Primary navigation', 'pergrazia' ),
			'footer'  => __( 'Footer navigation', 'pergrazia' ),
		)
	);
}
add_action( 'after_setup_theme', 'pergrazia_setup' );

/**
 * Set a comfortable content width for embeds and media.
 */
function pergrazia_content_width(): void {
	$GLOBALS['content_width'] = apply_filters( 'pergrazia_content_width', 736 );
}
add_action( 'after_setup_theme', 'pergrazia_content_width', 0 );

/**
 * Register the optional article sidebar widget area.
 */
function pergrazia_widgets_init(): void {
	register_sidebar(
		array(
			'name'          => __( 'Article sidebar', 'pergrazia' ),
			'id'            => 'article-sidebar',
			'description'   => __( 'Optional widgets displayed beside single articles.', 'pergrazia' ),
			'before_widget' => '<section id="%1$s" class="article-sidebar__widget widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="sidebar-heading">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'pergrazia_widgets_init' );

/**
 * Load theme assets.
 */
function pergrazia_assets(): void {
	wp_enqueue_style( 'pergrazia-style', get_stylesheet_uri(), array(), PERGRAZIA_VERSION );
	wp_enqueue_script(
		'pergrazia-navigation',
		get_template_directory_uri() . '/assets/js/navigation.js',
		array(),
		PERGRAZIA_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'pergrazia_assets' );

/**
 * Show one extra article on the first blog page without shifting later pages.
 *
 * Page one contains the featured story plus twelve cards. Subsequent pages
 * continue with twelve articles and an offset that prevents duplicates.
 *
 * @param WP_Query $query The current query.
 */
function pergrazia_blog_page_size( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_home() ) {
		return;
	}

	$paged = max( 1, (int) $query->get( 'paged' ) );

	if ( 1 === $paged ) {
		$query->set( 'posts_per_page', 13 );
		return;
	}

	$query->set( 'posts_per_page', 12 );
	$query->set( 'offset', 13 + ( ( $paged - 2 ) * 12 ) );
}
add_action( 'pre_get_posts', 'pergrazia_blog_page_size' );

/**
 * Correct the blog page count after applying the variable first-page size.
 *
 * @param WP_Post[] $posts The queried posts.
 * @param WP_Query  $query The current query.
 * @return WP_Post[]
 */
function pergrazia_blog_page_count( array $posts, WP_Query $query ): array {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_home() ) {
		return $posts;
	}

	$total = (int) $query->found_posts;

	if ( $total <= 13 ) {
		$query->max_num_pages = $total > 0 ? 1 : 0;
	} else {
		$query->max_num_pages = 1 + (int) ceil( ( $total - 13 ) / 12 );
	}

	return $posts;
}
add_filter( 'the_posts', 'pergrazia_blog_page_count', 10, 2 );

/**
 * Fallback navigation when no menu has been assigned yet.
 */
function pergrazia_page_menu(): void {
	wp_page_menu(
		array(
			'menu_class' => 'fallback-menu',
			'show_home'  => true,
			'container'  => false,
		)
	);
}

require get_template_directory() . '/inc/template-tags.php';
