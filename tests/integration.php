<?php
/** Run with wp eval-file tests/integration.php on a disposable WordPress site. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 'Run through WP-CLI on a disposable test site.' );
}
$brpw_test_ids = array();
$brpw_test_attachments = array();
$brpw_test_terms = array();
$GLOBALS['brpw_test_count'] = 0;
function brpw_expect( $condition, $message ) {
	global $brpw_test_count;
	++$brpw_test_count;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	WP_CLI::log( 'PASS ' . $message );
}
set_error_handler( function ( $severity, $message, $file, $line ) {
	if ( error_reporting() & $severity && false !== strpos( $file, '/beautiful-recent-posts-widget/' ) ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
	return false;
} );
try {
	$legacy = brpw_settings( array( 'title' => 'Old widget', 'totalnews' => 7, 'textbutton' => 'All stories', 'pageid' => 12 ) );
	brpw_expect( 7 === $legacy['totalnews'] && $legacy['show_date'] && $legacy['show_comments'] && 'circle' === $legacy['image_shape'], '4.1 settings retain display defaults' );
	$bad = brpw_settings( array( 'title' => '<script>bad</script>Title', 'totalnews' => 999999, 'layout' => '"><script>', 'show_date' => 'false', 'excerpt_length' => 9999, 'category' => array( 1 ), 'orderby' => 'rand' ) );
	brpw_expect( 20 === $bad['totalnews'] && 60 === $bad['excerpt_length'] && 'list' === $bad['layout'] && 'date' === $bad['orderby'] && 0 === $bad['category'] && false === $bad['show_date'] && false === strpos( $bad['title'], '<' ), 'hostile settings are bounded and sanitized' );

	$plugin_basename = plugin_basename( WP_PLUGIN_DIR . '/beautiful-recent-posts-widget/BRPWidget.php' );
	$old_basename = dirname( $plugin_basename ) . '/beautiful-recent-posts-widget.php';
	$registered_plugins = get_plugins( '/beautiful-recent-posts-widget' );
	brpw_expect( array( 'BRPWidget.php' ) === array_keys( $registered_plugins ), 'only the original directory entry point appears in the plugin list' );
	$original_active = get_option( 'active_plugins', array() );
	try {
		update_option( 'active_plugins', array( 'unrelated/plugin.php', $old_basename ) );
		brpw_migrate_activation_path();
		brpw_expect( array( 'unrelated/plugin.php', $plugin_basename ) === get_option( 'active_plugins' ), 'GitHub 4.x activation migrates without changing unrelated entries' );
		brpw_migrate_activation_path();
		brpw_expect( array( 'unrelated/plugin.php', $plugin_basename ) === get_option( 'active_plugins' ), 'activation migration is idempotent' );
	} finally {
		update_option( 'active_plugins', $original_active );
	}

	if ( is_multisite() ) {
		$original_network = get_site_option( 'active_sitewide_plugins', array() );
		try {
			update_site_option( 'active_sitewide_plugins', array( 'unrelated/plugin.php' => 123, $old_basename => 456 ) );
			brpw_migrate_activation_path();
			brpw_expect( array( 'unrelated/plugin.php' => 123, $plugin_basename => 456 ) === get_site_option( 'active_sitewide_plugins' ), 'network activation migration preserves timestamps and unrelated plugins' );
			brpw_migrate_activation_path();
			brpw_expect( array( 'unrelated/plugin.php' => 123, $plugin_basename => 456 ) === get_site_option( 'active_sitewide_plugins' ), 'network activation migration is idempotent' );
		} finally {
			update_site_option( 'active_sitewide_plugins', $original_network );
		}
	}
	$widget = new BRP_Widget();
	brpw_expect( 'brp_widget' === $widget->id_base, 'legacy widget ID is unchanged' );
	$saved = $widget->update( array( 'title' => 'New' ), array() );
	brpw_expect( ! $saved['show_image'] && ! $saved['show_date'] && 3 === $saved['totalnews'], 'unchecked checkboxes save false without undefined-key notices' );
	ob_start(); $widget->form( array() ); $form = ob_get_clean();
	brpw_expect( false !== strpos( $form, 'show_excerpt' ) && false !== strpos( $form, 'category' ), 'classic widget exposes new controls' );
	$category = wp_insert_term( 'BRPW test ' . wp_generate_uuid4(), 'category' );
	$brpw_test_terms[] = $category['term_id'];
	$other_category = wp_insert_term( 'BRPW other ' . wp_generate_uuid4(), 'category' );
	$brpw_test_terms[] = $other_category['term_id'];
	foreach ( array(
		array( 'post_title' => 'Alpha story', 'post_status' => 'publish' ),
		array( 'post_title' => 'Beta story', 'post_status' => 'publish' ),
		array( 'post_title' => 'Private story', 'post_status' => 'private' ),
		array( 'post_title' => 'Draft story', 'post_status' => 'draft' ),
		array( 'post_title' => 'Protected story', 'post_status' => 'publish', 'post_password' => 'secret' ),
		array( 'post_title' => '', 'post_status' => 'publish' ),
	) as $post_args ) {
		$id = wp_insert_post( array_merge( array( 'post_type' => 'post', 'post_content' => '<!-- wp:paragraph --><p>A useful story with enough words to make a readable short excerpt for a reader.</p><!-- /wp:paragraph --><!-- wp:brpw/recent-posts /--> [beautiful_recent_posts]', 'post_category' => array( $category['term_id'] ), 'comment_status' => 'closed' ), $post_args ), true );
		if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
		$brpw_test_ids[] = $id;
	}
	$settings = brpw_settings( array( 'category' => $category['term_id'], 'totalnews' => 20, 'orderby' => 'title', 'show_excerpt' => true ) );
	$posts = get_posts( brpw_query_args( $settings ) );
	brpw_expect( 3 === count( $posts ), 'query excludes drafts, private and password-protected posts' );
	$settings['exclude_current'] = true;
	$excluded = get_posts( brpw_query_args( $settings, $brpw_test_ids[0] ) );
	brpw_expect( 2 === count( $excluded ) && ! in_array( $brpw_test_ids[0], wp_list_pluck( $excluded, 'ID' ), true ), 'current-post exclusion honors explicit context' );
	$settings['exclude_current'] = false;
	$upload = wp_upload_bits( 'brpw-test-' . wp_generate_uuid4() . '.png', null, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aNioAAAAASUVORK5CYII=' ) );
	$attachment = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_status' => 'inherit', 'post_title' => 'BRPW test image' ), $upload['file'] );
	$brpw_test_attachments[] = $attachment;
	wp_update_attachment_metadata( $attachment, array( 'width' => 340, 'height' => 340, 'file' => _wp_relative_upload_path( $upload['file'] ), 'sizes' => array( 'brpw-thumb-widget-retina' => array( 'file' => basename( $upload['file'] ), 'width' => 170, 'height' => 170, 'mime-type' => 'image/png' ), 'brpw-thumb-widget' => array( 'file' => 'test-85.png', 'width' => 85, 'height' => 85, 'mime-type' => 'image/png' ) ) ) );
	set_post_thumbnail( $brpw_test_ids[0], $attachment );
	$image_html = brpw_render( $settings );
	brpw_expect( false !== strpos( $image_html, 'srcset=' ) && false !== strpos( $image_html, 'sizes=' ) && false !== strpos( $image_html, 'decoding="async"' ), 'sanitizer preserves native responsive image attributes' );
	set_post_format( $brpw_test_ids[0], 'quote' );
	brpw_expect( false === strpos( brpw_render( $settings ), '<img ' ), 'quote format retains legacy thumbnail suppression' );
	set_post_format( $brpw_test_ids[0], false );

	$before = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
	$html = brpw_render( $settings );
	brpw_expect( $before === ( isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null ), 'rendering leaves the global post untouched' );
	brpw_expect( 3 === substr_count( $html, '<li ' ) && false !== strpos( $html, 'Untitled post' ), 'all visible posts render and empty titles get accessible labels' );
	brpw_expect( false !== strpos( $html, 'brpw-excerpt' ) && false === strpos( $html, '[beautiful_recent_posts]' ) && 1 === substr_count( $html, '<ul ' ), 'excerpt strips shortcodes and avoids recursive block rendering' );
	brpw_expect( false !== strpos( $html, '<time ' ) && false === strpos( $html, 'class="link-comment"' ), 'dates are semantic and closed empty comments stay hidden' );
	brpw_expect( strpos( $html, 'Alpha story' ) < strpos( $html, 'Beta story' ), 'title ordering is ascending' );
	$minimal = brpw_render( array_merge( $settings, array( 'show_date' => false, 'show_comments' => false, 'show_author' => false, 'show_excerpt' => false, 'layout' => 'cards' ) ) );
	brpw_expect( false === strpos( $minimal, 'brpw-meta' ) && false === strpos( $minimal, 'brpw-excerpt' ) && false !== strpos( $minimal, 'brpw--cards' ), 'display toggles and card layout take effect' );
	brpw_expect( '' === brpw_render( array( 'category' => $other_category['term_id'] ) ), 'empty results emit no empty list' );
	$page = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'BRPW test page', 'post_status' => 'draft' ) );
	$brpw_test_ids[] = $page;
	$button_settings = array_merge( $settings, array( 'pageid' => $page, 'textbutton' => 'More stories' ) );
	brpw_expect( false === strpos( brpw_render( $button_settings ), 'brpw-button-more' ), 'draft button destinations are not exposed' );
	wp_update_post( array( 'ID' => $page, 'post_status' => 'publish' ) );
	brpw_expect( false !== strpos( brpw_render( $button_settings ), 'brpw-button-more' ), 'public button destinations render' );
	$shortcode = do_shortcode( '[beautiful_recent_posts category="' . $category['term_id'] . '" title="Stories" show_date="false"]' );
	brpw_expect( false !== strpos( $shortcode, 'brpw-heading' ) && false === strpos( $shortcode, '<time ' ), 'shortcode honors string booleans and title' );
	$block = WP_Block_Type_Registry::get_instance()->get_registered( 'brpw/recent-posts' );
	brpw_expect( $block && 3 === $block->api_version && $block->is_dynamic(), 'native block registers API v3 and dynamic rendering' );
	foreach ( brpw_defaults() as $key => $default ) {
		brpw_expect( $default === $block->attributes[ $key ]['default'], 'block and PHP default agree: ' . $key );
	}
	$block_html = do_blocks( '<!-- wp:brpw/recent-posts {"category":' . $category['term_id'] . ',"layout":"cards"} /-->' );
	brpw_expect( false !== strpos( $block_html, 'wp-block-brpw-recent-posts' ) && false !== strpos( $block_html, 'brpw--cards' ), 'saved block renders its wrapper and chosen layout' );
	WP_CLI::success( $GLOBALS['brpw_test_count'] . ' integration assertions passed on WordPress ' . get_bloginfo( 'version' ) . ' / PHP ' . PHP_VERSION );
} finally {
	foreach ( $brpw_test_ids as $id ) { wp_delete_post( $id, true ); }
	foreach ( $brpw_test_attachments as $id ) { wp_delete_attachment( $id, true ); }
	foreach ( $brpw_test_terms as $id ) { wp_delete_term( $id, 'category' ); }
	restore_error_handler();
}
