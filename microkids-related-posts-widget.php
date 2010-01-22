<?php

	class WP_Widget_Related_Posts extends WP_Widget {
		
		function WP_Widget_Related_Posts() {
			$widget_ops = array('classname' => 'widget_related_posts', 'description' => __( "Display related posts as a widget") );
			$this->WP_Widget('related_posts', __('Related Posts'), $widget_ops);
		}
		
		function widget( $args, $instance ) {
			
			extract( $args );
			
			if( is_single() || is_page() ) {

				global $post;
				
				$related_posts = MRP_get_related_posts( $post->ID );

				if( $related_posts ) {

					echo $before_widget;
					echo "<div id=\"related-posts-widget\">\n";
					echo "<h2>".$instance['title']."</h2>\n";
					echo "<ul>\n";

					foreach( $related_posts as $related_post_id => $related_post_title  ) {
						echo "<li><a href=\"".get_permalink( $related_post_id )."\">".$related_post_title."</a></li>\n";
					}

					echo "</ul></div>";

					echo $after_widget;

				}
				else {

					if( ! $instance['hide_if_empty'] ) {

						echo $before_widget;

						echo "<div id=\"related-posts-widget\">\n";					
						echo "<h2>".$instance['title']."</h2>\n";
						echo "<p>".$instance['text_if_empty']."</p>\n";
						echo "</div>\n";

						echo $after_widget;

					}
				}
			}
		}
		
		function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['title'] = htmlspecialchars( $new_instance['title'] );
			$instance['hide_if_empty'] = (int) $new_instance['hide_if_empty'];
			$instance['text_if_empty'] = htmlspecialchars( $new_instance['text_if_empty'] );
			return $instance;
		}
		
		function form( $instance ) {
			$instance = wp_parse_args( (array) $instance, array( 'title' => 'Related posts', 'hide_if_empty' => '0', 'text_if_empty' => 'None' ) );
			$title = attribute_escape( $instance['title'] );
			$hide_if_empty = attribute_escape( $instance['hide_if_empty'] );
			$text_if_empty = attribute_escape( $instance['text_if_empty'] );
?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>
			<p><label for="<?php echo $this->get_field_id('hide_if_empty') ?>-1"><?php _e('If there are no related posts:'); ?></label></p>
			<p><input type="radio" <?php checked( $instance['hide_if_empty'], '1' ); ?> name="<?php echo $this->get_field_name('hide_if_empty') ?>" value="1" id="<?php echo $this->get_field_id('hide_if_empty') ?>-1" /> <label for="<?php echo $this->get_field_id('hide_if_empty') ?>-1">Hide the entire widget</label></p>
			<p><input type="radio" <?php checked( $instance['hide_if_empty'], '0' ); ?> name="<?php echo $this->get_field_name('hide_if_empty') ?>" value="0" id="<?php echo $this->get_field_id('hide_if_empty') ?>-2" /> <label for="<?php echo $this->get_field_id('hide_if_empty') ?>-2">Show this text:</label>
			<input class="widefat" id="<?php echo $this->get_field_id('text_if_empty') ?>" name="<?php echo $this->get_field_name('text_if_empty') ?>" type="text" value="<?php echo $text_if_empty; ?>" /></p>
<?php			
		}
	}
?>