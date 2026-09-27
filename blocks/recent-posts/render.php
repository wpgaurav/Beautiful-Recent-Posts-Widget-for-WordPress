<?php
/**
 * Dynamic block view.
 *
 * @package BeautifulRecentPosts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$brpw_settings = brpw_settings( $attributes );
$brpw_html     = brpw_render( $brpw_settings, isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : 0 );
if ( '' === $brpw_html ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $brpw_settings['title'] ) : ?>
		<h2 class="brpw-heading"><?php echo esc_html( $brpw_settings['title'] ); ?></h2>
	<?php endif; ?>
	<?php echo $brpw_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
