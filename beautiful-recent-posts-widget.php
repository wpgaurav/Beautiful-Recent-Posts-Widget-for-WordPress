<?php
/**
 * Plugin bootstrap and compatibility entry point for GitHub versions 4.x.
 *
 * @package BeautifulRecentPosts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BRPW_PLUGIN_URI', plugins_url( '', __FILE__ ) );
define( 'BRPW_VERSION', '5.0.0' );

require_once __DIR__ . '/includes/render.php';
require_once __DIR__ . '/includes/class-brp-widget.php';

/** Register assets and the dynamic editor block. */
function brpw_init() {
	add_image_size( 'brpw-thumb-widget', 85, 85, true );
	add_image_size( 'brpw-thumb-widget-retina', 170, 170, true );
	wp_register_style( 'beautiful-recent-posts-style', BRPW_PLUGIN_URI . '/css/brpw.css', array(), BRPW_VERSION );
	wp_register_script(
		'brpw-editor',
		BRPW_PLUGIN_URI . '/blocks/recent-posts/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render', 'wp-data', 'wp-core-data' ),
		BRPW_VERSION,
		true
	);
	wp_set_script_translations( 'brpw-editor', 'beautiful-recent-posts-widget' );
	register_block_type( __DIR__ . '/blocks/recent-posts' );
	add_shortcode( 'beautiful_recent_posts', 'brpw_shortcode' );
}
add_action( 'init', 'brpw_init' );

/** Keep the original widget ID and class for existing sidebars. */
function brpw_register_widget() {
	register_widget( 'BRP_Widget' );
}
add_action( 'widgets_init', 'brpw_register_widget' );

/** Classic widgets need their CSS queued before wp_head prints styles. */
function brpw_enqueue_widget_style() {
	if ( is_active_widget( false, false, 'brp_widget', true ) ) {
		wp_enqueue_style( 'beautiful-recent-posts-style' );
	}
}
add_action( 'wp_enqueue_scripts', 'brpw_enqueue_widget_style' );

/**
 * Preserve activation for installations of the 4.x GitHub-only filename.
 *
 * The directory's original BRPWidget.php remains the canonical plugin header.
 * This runs before WordPress validates active plugin headers in wp-admin.
 */
function brpw_migrate_activation_path() {
	$previous = plugin_basename( __FILE__ );
	$current  = plugin_basename( __DIR__ . '/BRPWidget.php' );
	$active   = get_option( 'active_plugins', array() );
	if ( is_array( $active ) && in_array( $previous, $active, true ) ) {
		foreach ( $active as &$plugin ) {
			if ( $previous === $plugin ) {
				$plugin = $current;
			}
		}
		unset( $plugin );
		update_option( 'active_plugins', array_values( array_unique( $active ) ) );
	}
	if ( is_multisite() ) {
		$network = get_site_option( 'active_sitewide_plugins', array() );
		if ( is_array( $network ) && isset( $network[ $previous ] ) ) {
			if ( ! isset( $network[ $current ] ) ) {
				$network[ $current ] = $network[ $previous ];
			}
			unset( $network[ $previous ] );
			update_site_option( 'active_sitewide_plugins', $network );
		}
	}
}
add_action( 'plugins_loaded', 'brpw_migrate_activation_path', 1 );
