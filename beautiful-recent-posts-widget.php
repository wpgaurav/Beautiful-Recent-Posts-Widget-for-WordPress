<?php
/**
 * Plugin Name: Beautiful Recent Posts Widget
 * Plugin URI: https://github.com/wpgaurav/Beautiful-Recent-Posts-Widget-for-WordPress
 * Version: 5.0.0
 * Description: Give your next great read a place to shine. Recent posts with thumbnails, flexible layouts, a block, and a classic widget.
 * Author: Gaurav Tiwari
 * Author URI: https://gauravtiwari.org
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: beautiful-recent-posts-widget
 * Requires at least: 6.6
 * Requires PHP: 7.4
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
