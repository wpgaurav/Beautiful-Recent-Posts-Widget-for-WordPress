<?php
/*
 * Plugin Name: Beautiful Recent Posts Widget
 * Plugin URI: http://gauravtiwari.org/portfolio/beautiful-recent-posts/
 * Version: 1.0
 * Description: Show your recent articles in a beautiful and minimal way! Lightweight and Simple.
 * Author: Gaurav Tiwari
 * Author URI: http://gauravtiwari.org
 * License: GPLv2 or later
 */
 if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
define( 'BRPW_PLUGIN_URI', plugins_url('', __FILE__) );

add_image_size( 'brpw-thumb-widget', 85, 85, true );
add_image_size( 'brpw-thumb-widget-retina', 170, 170, true ); // Assigning thumbnails


class BRP_Widget extends WP_Widget {

	function BRP_Widget() {
		$widgets_opt = array('description'=>__('A beautiful and minimal widget to show latest posts with featured images.','brpw'));
		parent::WP_Widget(false,$name= "Beautiful Recent Posts",$widgets_opt);
	}

	function widget( $args, $instance ) {
		extract($args);
		global $post;
		$title = apply_filters( 'widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
		$textbutton = apply_filters( 'widget_textbutton', $instance['textbutton'], $instance );
		$totalnews = apply_filters( 'widget_totalnews', $instance['totalnews'], $instance );
		$pageid = apply_filters('pageid', $instance['pageid'], $instance);
		
		$r = new WP_Query(array('showposts' => $totalnews, 'nopaging' => 0, 'post_status' => 'publish', 'ignore_sticky_posts' => 1));
		if ($r->have_posts()) :
		echo $before_widget;
		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; } 		
    
		echo "<ul class=\"menu brpw-news-sidebar\">";
		while ($r->have_posts()) : $r->the_post(); 
		$urlimage =  wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'brpw-thumb-widget' );
		$urlimageretina = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'brpw-thumb-widget-retina' ); 
		?>
			<li class="brpw-clearfix">
				<?php if ((get_post_format()<>'quote') AND (get_post_format()<>'aside')): ?>
				<?php if ( has_post_thumbnail() ) : ?><img src="<?php echo $urlimage[0];?>" data-retina="<?php echo $urlimageretina[0];?>" alt="<?php the_title();?>" class="brpw-imgframe alignleft" /><?php endif;?>
				<?php endif;?>
				<h4 style="font-style:bold;"><a href="<?php the_permalink();?>"><?php the_title();?></a></h4>
				<span class="brpw-date-news"><?php the_time('F j, Y'); ?></span>
				 <h5><?php comments_popup_link(__('No Comment','brpw'), __('Comment (1)','brpw'), __('Comments (%) ','brpw'), 'link-comment');?></h5>
			</li>
		<?php endwhile; ?>
		</ul>
		<?php if ( !empty( $textbutton ) ) : ?>
			<a href="<?php echo get_page_link($pageid);?>" class="brpw-button-more"><?php echo $textbutton; ?></a>
		<?php endif; ?>
		<?php
		echo $after_widget;
		// Reset the global $the_post as this query will have stomped on it
		wp_reset_postdata();

		endif;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['textbutton'] = strip_tags($new_instance['textbutton']);
		$instance['totalnews'] = strip_tags($new_instance['totalnews']);
		$instance['pageid'] = strip_tags($new_instance['pageid']);
		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'totalnews' => '', 'textbutton' =>'', 'pageid' => '' ) );
		$title = strip_tags($instance['title']);
		$textbutton = strip_tags($instance['textbutton']);
		$totalnews = strip_tags($instance['totalnews']);
		$pageid = strip_tags($instance['pageid']);
		
		$pages = get_pages();
		$listpages = array();
		foreach ($pages as $pagelist ) {
		   $listpages[$pagelist->ID] = $pagelist->post_title;
		}
		
		$totalnews = (int)($instance['totalnews']);
		if ( $totalnews < 1 || 10 < $totalnews )
			$totalnews  = 3;
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:','brpw');?></label>
		<input class="brpwidget" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>
		<p><label for="<?php echo $this->get_field_name('totalnews'); ?>"><?php _e('Number of posts to show:','brpw');?></label>
		<select  name="<?php echo $this->get_field_name('totalnews'); ?>"  id="<?php echo $this->get_field_id('totalnews'); ?>" class="brpwidget" >
		<?php
		for ( $i = 1; $i <= 10; ++$i )
			echo "<option value='$i' " . ( $totalnews == $i ? "selected='selected'" : '' ) . ">$i</option>";
		?>
		</select></p>
		<p><label for="<?php echo $this->get_field_id('textbutton'); ?>"><?php _e('Text for Button:','brpw');?></label>
		<input class="brpwidget" id="<?php echo $this->get_field_id('textbutton'); ?>" name="<?php echo $this->get_field_name('textbutton'); ?>" type="text" value="<?php echo esc_attr($textbutton); ?>" /></p>
		<p><label for="<?php echo $this->get_field_name('pageid'); ?>"><?php _e('Link goes to:','brpw');?></label>
		<select  name="<?php echo $this->get_field_name('pageid'); ?>"  id="<?php echo $this->get_field_id('pageid'); ?>" class="brpwidget" >
			<?php foreach ($listpages as $opt => $val) { ?>
		<option value="<?php echo $opt ;?>" <?php if ( $pageid  == $opt) { echo ' selected="selected" '; }?>><?php echo $val; ?></option>
		<?php } ?>
		</select></p>
<?php
	}
}

add_action('widgets_init', create_function('', 'return register_widget("BRP_Widget");'));
wp_enqueue_style( 'beautiful-recent-posts-style', BRPW_PLUGIN_URI . '/css/brpw.css');
?>