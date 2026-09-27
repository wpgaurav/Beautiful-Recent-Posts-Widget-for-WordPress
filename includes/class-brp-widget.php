<?php
/**
 * Backwards-compatible classic widget.
 *
 * @package BeautifulRecentPosts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Preserve classic widget registration and saved instances. */
class BRP_Widget extends WP_Widget {
	/** Register the legacy widget identity. */
	public function __construct() {
		parent::__construct(
			'brp_widget',
			__( 'Beautiful Recent Posts', 'beautiful-recent-posts-widget' ),
			array(
				'classname'   => 'brp_widget',
				'description' => __( 'Recent stories with thoughtful layouts, thumbnails and flexible filters.', 'beautiful-recent-posts-widget' ),
			)
		);
	}

	/**
	 * Display the widget.
	 *
	 * @param array $args Theme wrappers.
	 * @param array $instance Saved settings.
	 */
	public function widget( $args, $instance ) {
		$settings = brpw_settings( $instance );
		$html     = brpw_render( $settings );
		if ( '' === $html ) {
			return;
		}
		$title = apply_filters( 'widget_title', $settings['title'], $instance, $this->id_base );
		// Theme-provided widget wrappers and the shared renderer are trusted HTML.
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Sanitize submitted settings.
	 *
	 * @param array $new_instance Submitted settings.
	 * @param array $old_instance Previous settings.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$new_instance = is_array( $new_instance ) ? $new_instance : array();
		foreach ( brpw_defaults() as $key => $default ) {
			if ( is_bool( $default ) ) {
				$new_instance[ $key ] = isset( $new_instance[ $key ] ) ? $new_instance[ $key ] : false;
			}
		}
		return brpw_settings( $new_instance );
	}

	/**
	 * Render classic widget controls.
	 *
	 * @param array $instance Saved settings.
	 */
	public function form( $instance ) {
		$settings = brpw_settings( $instance );
		foreach ( array(
			'title'          => __( 'Title', 'beautiful-recent-posts-widget' ),
			'totalnews'      => __( 'Number of posts (1–20)', 'beautiful-recent-posts-widget' ),
			'excerpt_length' => __( 'Excerpt length (5–60 words)', 'beautiful-recent-posts-widget' ),
			'textbutton'     => __( 'Button text (optional)', 'beautiful-recent-posts-widget' ),
		) as $key => $label ) {
			$numeric = in_array( $key, array( 'totalnews', 'excerpt_length' ), true );
			?>
			<p><label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo esc_html( $label ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" type="<?php echo $numeric ? 'number' : 'text'; ?>" value="<?php echo esc_attr( $settings[ $key ] ); ?>"
			<?php
			if ( $numeric ) :
				?>
				min="<?php echo 'totalnews' === $key ? '1' : '5'; ?>" max="<?php echo 'totalnews' === $key ? '20' : '60'; ?>"<?php endif; ?> /></p>
			<?php
		}
		$categories = array( 0 => __( 'All categories', 'beautiful-recent-posts-widget' ) );
		foreach ( get_categories( array( 'hide_empty' => false ) ) as $category ) {
			$categories[ $category->term_id ] = $category->name;
		}
		$pages = array( 0 => __( 'No button destination', 'beautiful-recent-posts-widget' ) );
		foreach ( get_pages( array( 'post_status' => 'publish' ) ) as $page ) {
			$pages[ $page->ID ] = $page->post_title;
		}
		$selects = array(
			'category'    => array( __( 'Category', 'beautiful-recent-posts-widget' ), $categories ),
			'layout'      => array(
				__( 'Layout', 'beautiful-recent-posts-widget' ),
				array(
					'list'  => __( 'Editorial list', 'beautiful-recent-posts-widget' ),
					'cards' => __( 'Cards', 'beautiful-recent-posts-widget' ),
				),
			),
			'image_shape' => array(
				__( 'List thumbnail shape', 'beautiful-recent-posts-widget' ),
				array(
					'circle'  => __( 'Circle', 'beautiful-recent-posts-widget' ),
					'rounded' => __( 'Rounded', 'beautiful-recent-posts-widget' ),
					'square'  => __( 'Square', 'beautiful-recent-posts-widget' ),
				),
			),
			'orderby'     => array(
				__( 'Order posts by', 'beautiful-recent-posts-widget' ),
				array(
					'date'     => __( 'Newest first', 'beautiful-recent-posts-widget' ),
					'modified' => __( 'Recently updated', 'beautiful-recent-posts-widget' ),
					'title'    => __( 'Title A–Z', 'beautiful-recent-posts-widget' ),
				),
			),
			'pageid'      => array( __( 'Button destination', 'beautiful-recent-posts-widget' ), $pages ),
		);
		foreach ( $selects as $key => $field ) :
			?>
			<p><label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo esc_html( $field[0] ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>">
				<?php foreach ( $field[1] as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings[ $key ], $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select></p>
			<?php
		endforeach;
		foreach ( array(
			'show_image'      => __( 'Show featured images', 'beautiful-recent-posts-widget' ),
			'show_date'       => __( 'Show date', 'beautiful-recent-posts-widget' ),
			'show_author'     => __( 'Show author', 'beautiful-recent-posts-widget' ),
			'show_comments'   => __( 'Show comment count', 'beautiful-recent-posts-widget' ),
			'show_excerpt'    => __( 'Show excerpt', 'beautiful-recent-posts-widget' ),
			'exclude_current' => __( 'Exclude the current post', 'beautiful-recent-posts-widget' ),
		) as $key => $label ) :
			?>
			<p><input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" value="1" <?php checked( $settings[ $key ] ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo esc_html( $label ); ?></label></p>
			<?php
		endforeach;
	}
}
