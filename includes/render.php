<?php
/**
 * Shared settings and renderer for widgets, blocks and shortcodes.
 *
 * @package BeautifulRecentPosts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Defaults deliberately retain the old widget's display choices. */
function brpw_defaults() {
	return array(
		'title'           => '',
		'totalnews'       => 3,
		'textbutton'      => '',
		'pageid'          => 0,
		'category'        => 0,
		'orderby'         => 'date',
		'layout'          => 'list',
		'image_shape'     => 'circle',
		'show_image'      => true,
		'show_date'       => true,
		'show_comments'   => true,
		'show_author'     => false,
		'show_excerpt'    => false,
		'exclude_current' => false,
		'excerpt_length'  => 20,
	);
}

/**
 * Normalize all entry points, including old or malformed saved instances.
 *
 * @param mixed $input Settings to normalize.
 * @return array
 */
function brpw_settings( $input ) {
	$defaults = brpw_defaults();
	$input    = is_array( $input ) ? $input : array();
	$output   = $defaults;
	foreach ( $defaults as $key => $default ) {
		if ( ! isset( $input[ $key ] ) || ! is_scalar( $input[ $key ] ) ) {
			continue;
		}
		$value = $input[ $key ];
		if ( is_bool( $default ) ) {
			$output[ $key ] = in_array( $value, array( true, 1, '1', 'true', 'yes', 'on' ), true );
		} elseif ( is_int( $default ) ) {
			$output[ $key ] = absint( $value );
		} else {
			$output[ $key ] = sanitize_text_field( (string) $value );
		}
	}
	$output['totalnews']      = max( 1, min( 20, $output['totalnews'] ) );
	$output['excerpt_length'] = max( 5, min( 60, $output['excerpt_length'] ) );
	foreach ( array(
		'layout'      => array( 'list', 'cards' ),
		'image_shape' => array( 'circle', 'rounded', 'square' ),
		'orderby'     => array( 'date', 'modified', 'title' ),
	) as $key => $allowed ) {
		if ( ! in_array( $output[ $key ], $allowed, true ) ) {
			$output[ $key ] = $defaults[ $key ];
		}
	}
	return $output;
}

/**
 * Generate safe query arguments without pagination totals or protected content.
 *
 * @param array $settings Normalized settings.
 * @param int   $current_post_id Post to exclude when requested.
 * @return array
 */
function brpw_query_args( $settings, $current_post_id = 0 ) {
	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'has_password'        => false,
		'posts_per_page'      => $settings['totalnews'],
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'orderby'             => $settings['orderby'],
		'order'               => 'title' === $settings['orderby'] ? 'ASC' : 'DESC',
	);
	if ( $settings['category'] ) {
		$args['cat'] = $settings['category'];
	}
	if ( $settings['exclude_current'] && $current_post_id ) {
		$args['post__not_in'] = array( absint( $current_post_id ) );
	}
	return $args;
}

/**
 * Return the shared post list without changing global post state.
 *
 * @param array $input Settings.
 * @param int   $current_post_id Explicit block context or current singular post.
 * @return string Escaped HTML, or an empty string when no posts match.
 */
function brpw_render( $input, $current_post_id = 0 ) {
	$settings = brpw_settings( $input );
	if ( ! $current_post_id && is_singular() ) {
		$current_post_id = get_queried_object_id();
	}
	$query = new WP_Query( brpw_query_args( $settings, $current_post_id ) );
	if ( ! $query->posts ) {
		return '';
	}
	wp_enqueue_style( 'beautiful-recent-posts-style' );
	ob_start();
	// Shortcodes and dynamically invoked widgets may run after the document head.
	// Print a registered stylesheet once in that case, rather than leaving them unstyled.
	if ( did_action( 'wp_head' ) && ! wp_style_is( 'beautiful-recent-posts-style', 'done' ) ) {
		wp_print_styles( array( 'beautiful-recent-posts-style' ) );
	}
	?>
	<div class="brpw brpw--<?php echo esc_attr( $settings['layout'] ); ?> brpw--<?php echo esc_attr( $settings['image_shape'] ); ?>">
		<ul class="brpw-news-sidebar" role="list">
			<?php
			foreach ( $query->posts as $item ) :
				$title     = get_the_title( $item );
				$title     = '' !== trim( $title ) ? $title : __( 'Untitled post', 'beautiful-recent-posts-widget' );
				$permalink = get_permalink( $item );
				$image     = '';
				if ( $settings['show_image'] && ! in_array( get_post_format( $item ), array( 'quote', 'aside' ), true ) ) {
					$image = get_the_post_thumbnail(
						$item,
						'cards' === $settings['layout'] ? 'medium_large' : 'brpw-thumb-widget-retina',
						array(
							'class'    => 'brpw-imgframe',
							'alt'      => '',
							'loading'  => 'lazy',
							'decoding' => 'async',
							'sizes'    => 'cards' === $settings['layout'] ? '(max-width: 600px) 100vw, 400px' : '85px',
						)
					);
				}
				?>
				<li class="brpw-item brpw-clearfix<?php echo $image ? ' brpw-item--image' : ''; ?>">
					<a class="brpw-post-link" href="<?php echo esc_url( $permalink ); ?>">
						<?php if ( $image ) : ?>
							<span class="brpw-media">
							<?php
							echo wp_kses(
								$image,
								array(
									'img' => array(
										'src'           => true,
										'srcset'        => true,
										'sizes'         => true,
										'alt'           => true,
										'width'         => true,
										'height'        => true,
										'class'         => true,
										'loading'       => true,
										'decoding'      => true,
										'fetchpriority' => true,
									),
								)
							);
							?>
														</span>
						<?php endif; ?>
						<span class="brpw-post-title"><?php echo esc_html( $title ); ?></span>
					</a>
					<?php if ( $settings['show_date'] || $settings['show_author'] || ( $settings['show_comments'] && ( comments_open( $item ) || get_comments_number( $item ) ) ) ) : ?>
						<div class="brpw-meta">
							<?php if ( $settings['show_date'] ) : ?>
								<time class="brpw-date-news" datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $item ) ); ?>"><?php echo esc_html( get_the_date( '', $item ) ); ?></time>
							<?php endif; ?>
							<?php if ( $settings['show_author'] ) : ?>
								<span class="brpw-author"><?php echo esc_html( get_the_author_meta( 'display_name', $item->post_author ) ); ?></span>
							<?php endif; ?>
							<?php if ( $settings['show_comments'] && ( comments_open( $item ) || get_comments_number( $item ) ) ) : ?>
								<a class="link-comment" href="<?php echo esc_url( get_comments_link( $item ) ); ?>">
								<?php
									$count = (int) get_comments_number( $item );
									/* translators: %s: number of comments. */
									echo esc_html( sprintf( _n( '%s comment', '%s comments', $count, 'beautiful-recent-posts-widget' ), number_format_i18n( $count ) ) );
								?>
								</a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
					<?php
					if ( $settings['show_excerpt'] ) :
						// Do not execute content filters/shortcodes: a post can contain this very block.
						$excerpt = $item->post_excerpt ? $item->post_excerpt : excerpt_remove_blocks( $item->post_content );
						$excerpt = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $excerpt ) ), $settings['excerpt_length'] );
						if ( $excerpt ) :
							?>
							<p class="brpw-excerpt"><?php echo esc_html( $excerpt ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
		$page = $settings['pageid'] ? get_post( $settings['pageid'] ) : null;
		if ( $settings['textbutton'] && $page && 'page' === $page->post_type && is_post_publicly_viewable( $page ) && ! $page->post_password ) :
			?>
			<a class="brpw-button-more" href="<?php echo esc_url( get_permalink( $page ) ); ?>"><?php echo esc_html( $settings['textbutton'] ); ?><span aria-hidden="true"> →</span></a>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Public shortcode shares the same bounded settings.
 *
 * @param array|string $attributes Shortcode attributes.
 * @return string
 */
function brpw_shortcode( $attributes ) {
	$settings = brpw_settings( shortcode_atts( brpw_defaults(), is_array( $attributes ) ? $attributes : array(), 'beautiful_recent_posts' ) );
	$html     = brpw_render( $settings );
	if ( '' === $html ) {
		return '';
	}
	$title = $settings['title'] ? '<h2 class="brpw-heading">' . esc_html( $settings['title'] ) . '</h2>' : '';
	return '<div class="brpw-shortcode">' . $title . $html . '</div>';
}
