<?php
/**
 * Plugin Name: Beautiful Recent Posts Widget
 * Plugin URI: http://gauravtiwari.org/portfolio/beautiful-recent-posts/
 * Version: 4.1
 * Description: Show your recent articles in a beautiful and minimal way! Lightweight and Simple.
 * Author: Gaurav Tiwari
 * Author URI: http://gauravtiwari.org
 * License: GPLv2 or later
 * Text Domain: brpw
 * Requires at least: 5.0
 * Requires PHP: 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'BRPW_PLUGIN_URI', plugins_url( '', __FILE__ ) );
define( 'BRPW_VERSION', '4.1' );

add_image_size( 'brpw-thumb-widget', 85, 85, true );
add_image_size( 'brpw-thumb-widget-retina', 170, 170, true ); // Assigning thumbnails


class BRP_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'brp_widget',
			'description' => __( 'A beautiful and minimal widget to show latest posts with featured images.', 'brpw' ),
		);
		parent::__construct( 'brp_widget', __( 'Beautiful Recent Posts', 'brpw' ), $widget_ops );
	}

	/**
	 * Output the widget content.
	 *
	 * @param array $args     Display arguments.
	 * @param array $instance Settings for the current widget instance.
	 */
	public function widget( $args, $instance ) {
		// Enqueue CSS only when widget is displayed
		wp_enqueue_style( 'beautiful-recent-posts-style', BRPW_PLUGIN_URI . '/css/brpw.css', array(), BRPW_VERSION );

		$title      = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		$textbutton = ! empty( $instance['textbutton'] ) ? $instance['textbutton'] : '';
		$totalnews  = ! empty( $instance['totalnews'] ) ? absint( $instance['totalnews'] ) : 3;
		$pageid     = ! empty( $instance['pageid'] ) ? absint( $instance['pageid'] ) : 0;

		$query_args = array(
			'posts_per_page'      => $totalnews,
			'no_found_rows'       => true,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
		);

		$r = new WP_Query( $query_args );

		if ( ! $r->have_posts() ) {
			return;
		}

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		echo '<ul class="menu brpw-news-sidebar">';

		while ( $r->have_posts() ) :
			$r->the_post();
			$post_format = get_post_format();
			?>
			<li class="brpw-clearfix">
				<?php if ( 'quote' !== $post_format && 'aside' !== $post_format ) : ?>
					<?php if ( has_post_thumbnail() ) :
						$urlimage       = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'brpw-thumb-widget' );
						$urlimageretina = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'brpw-thumb-widget-retina' );
						?>
						<img src="<?php echo esc_url( $urlimage[0] ); ?>"
						     data-retina="<?php echo esc_url( $urlimageretina[0] ); ?>"
						     alt="<?php echo esc_attr( get_the_title() ); ?>"
						     class="brpw-imgframe alignleft" />
					<?php endif; ?>
				<?php endif; ?>
				<h4 class="brpw-post-title"><a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a></h4>
				<span class="brpw-date-news"><?php echo esc_html( get_the_time( 'F j, Y' ) ); ?></span>
				<h5><?php comments_popup_link( __( 'No Comment', 'brpw' ), __( 'Comment (1)', 'brpw' ), __( 'Comments (%)', 'brpw' ), 'link-comment' ); ?></h5>
			</li>
		<?php endwhile; ?>
		</ul>

		<?php if ( ! empty( $textbutton ) && $pageid > 0 ) : ?>
			<a href="<?php echo esc_url( get_page_link( $pageid ) ); ?>" class="brpw-button-more"><?php echo esc_html( $textbutton ); ?></a>
		<?php endif; ?>

		<?php
		echo $args['after_widget'];

		// Reset the global $the_post as this query will have stomped on it
		wp_reset_postdata();
	}

	/**
	 * Update widget instance.
	 *
	 * @param array $new_instance New settings for this instance.
	 * @param array $old_instance Old settings for this instance.
	 * @return array Updated settings.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                = array();
		$instance['title']       = sanitize_text_field( $new_instance['title'] );
		$instance['textbutton']  = sanitize_text_field( $new_instance['textbutton'] );
		$instance['totalnews']   = absint( $new_instance['totalnews'] );
		$instance['pageid']      = absint( $new_instance['pageid'] );
		return $instance;
	}

	/**
	 * Output the widget settings form.
	 *
	 * @param array $instance Current settings.
	 */
	public function form( $instance ) {
		$instance = wp_parse_args(
			(array) $instance,
			array(
				'title'      => '',
				'totalnews'  => 3,
				'textbutton' => '',
				'pageid'     => 0,
			)
		);

		$title      = sanitize_text_field( $instance['title'] );
		$textbutton = sanitize_text_field( $instance['textbutton'] );
		$totalnews  = absint( $instance['totalnews'] );
		$pageid     = absint( $instance['pageid'] );

		if ( $totalnews < 1 || 10 < $totalnews ) {
			$totalnews = 3;
		}

		$pages = get_pages();
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'brpw' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'totalnews' ) ); ?>"><?php esc_html_e( 'Number of posts to show:', 'brpw' ); ?></label>
			<select name="<?php echo esc_attr( $this->get_field_name( 'totalnews' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'totalnews' ) ); ?>" class="widefat">
				<?php for ( $i = 1; $i <= 10; ++$i ) : ?>
					<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $totalnews, $i ); ?>><?php echo esc_html( $i ); ?></option>
				<?php endfor; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'textbutton' ) ); ?>"><?php esc_html_e( 'Text for Button:', 'brpw' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'textbutton' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'textbutton' ) ); ?>" type="text" value="<?php echo esc_attr( $textbutton ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'pageid' ) ); ?>"><?php esc_html_e( 'Link goes to:', 'brpw' ); ?></label>
			<select name="<?php echo esc_attr( $this->get_field_name( 'pageid' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'pageid' ) ); ?>" class="widefat">
				<option value="0"><?php esc_html_e( '-- Select a page --', 'brpw' ); ?></option>
				<?php foreach ( $pages as $page ) : ?>
					<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $pageid, $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}
}

/**
 * Register the widget.
 */
function brpw_register_widget() {
	register_widget( 'BRP_Widget' );
}
add_action( 'widgets_init', 'brpw_register_widget' );