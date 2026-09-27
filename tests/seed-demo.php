<?php
/** Demo fixtures only: use on a disposable local QA site. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit; }
if ( get_option( 'brpw_demo_page' ) ) { WP_CLI::log( (string) get_option( 'brpw_demo_page' ) ); return; }
require_once ABSPATH . 'wp-admin/includes/image.php';
$category = wp_insert_term( 'Field Notes', 'category' );
$category_id = is_wp_error( $category ) ? $category->get_error_data( 'term_exists' ) : $category['term_id'];
$source = WP_PLUGIN_DIR . '/beautiful-recent-posts-widget/assets/banner-1544x500.png';
$upload = wp_upload_bits( 'brpw-editorial.png', null, file_get_contents( $source ) );
$attachment = wp_insert_attachment( array( 'post_title' => 'Beautiful Recent Posts editorial illustration', 'post_mime_type' => 'image/png', 'post_status' => 'inherit' ), $upload['file'] );
wp_update_attachment_metadata( $attachment, wp_generate_attachment_metadata( $attachment, $upload['file'] ) );
foreach ( array( 'Make room for your next good idea', 'A quieter corner of the internet', 'Small details, better reading', 'A story without a featured image' ) as $index => $title ) {
	$id = wp_insert_post( array( 'post_title' => $title, 'post_status' => 'publish', 'post_content' => '<!-- wp:paragraph --><p>A good reading experience starts with the details. Clear typography, useful context, and room to breathe help readers find their next story.</p><!-- /wp:paragraph -->', 'post_category' => array( $category_id ), 'comment_status' => 'open', 'post_date' => gmdate( 'Y-m-d H:i:s', time() - $index * DAY_IN_SECONDS ) ) );
	if ( $index < 3 ) { set_post_thumbnail( $id, $attachment ); }
}
$content = '<!-- wp:heading --><h2 class="wp-block-heading">The editorial list</h2><!-- /wp:heading -->';
$content .= '<!-- wp:brpw/recent-posts ' . wp_json_encode( array( 'category' => (int) $category_id, 'image_shape' => 'rounded', 'totalnews' => 4, 'show_author' => true, 'show_excerpt' => true ) ) . ' /-->';
$content .= '<!-- wp:heading --><h2 class="wp-block-heading">Room for every story</h2><!-- /wp:heading -->';
$content .= '<!-- wp:brpw/recent-posts ' . wp_json_encode( array( 'category' => (int) $category_id, 'layout' => 'cards', 'totalnews' => 3, 'show_excerpt' => true, 'show_comments' => false ) ) . ' /-->';
$content .= '<!-- wp:heading --><h2 class="wp-block-heading">The shortcode</h2><!-- /wp:heading --><!-- wp:shortcode -->[beautiful_recent_posts category="' . $category_id . '" totalnews="2" show_image="false" show_date="false"]<!-- /wp:shortcode -->';
$page = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'A place for your next great read', 'post_status' => 'publish', 'post_content' => $content ) );
update_option( 'brpw_demo_page', $page );
WP_CLI::log( 'Demo page: ' . $page . ' Category: ' . $category_id );
