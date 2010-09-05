<?php
class WP_Widget_Related_Posts extends WP_Widget {

    /**
     * WP_Widget_Related_Posts - Constructor function 
     */
	function WP_Widget_Related_Posts() {
		$widget_ops = array('classname' => 'widget_related_posts', 'description' => __( "Display related posts as a widget") );
		$this->WP_Widget('related_posts', __('Related Posts'), $widget_ops);
	}
	
	/**
	 * widget - Standard function called to display widget contents
	 *
	 * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
 	 * @param array $instance The settings for the particular instance of the widget
	 */
	function widget( $args, $instance ) {
		extract( $args );
		if( is_single() || is_page() ) {
		    global $post;
			$post_type = ( $instance['post_type'] == 'all' ) ? null : $instance['post_type'];
			$related_posts = MRP_get_related_posts( $post->ID, 0, 0, $post_type );
            if( $related_posts ) {
	            echo $before_widget;
				echo "<div id=\"related-posts-widget\">\n";
				echo "<h3 class=\"widget-title\">".$instance['title']."</h3>\n";
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
					echo "<h3 class=\"widget-title\">".$instance['title']."</h3>\n";
					echo "<p>".$instance['text_if_empty']."</p>\n";
					echo "</div>\n";
					echo $after_widget;
				}
			}
		}
	}
	
	/**
	 * update - Save the settings for the widgets
	 *
	 * @param array $new_instance New settings for this instance as input by the user via form()
 	 * @param array $old_instance Old settings for this instance
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = htmlspecialchars( $new_instance['title'] );
		$instance['post_type'] = htmlspecialchars( $new_instance['post_type'] );
		$instance['hide_if_empty'] = (int) $new_instance['hide_if_empty'];
		$instance['text_if_empty'] = htmlspecialchars( $new_instance['text_if_empty'] );
		return $instance;
	}
	
	/**
	 * form - Create the form for the widget
	 *
	 * @param array $instance Current settings
	 */
	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => 'Related posts', 'hide_if_empty' => '0', 'text_if_empty' => __('None', 'microkids-related-posts'), 'post_type' => 'all' ) );
		$title = esc_attr( $instance['title'] );
		$hide_if_empty = esc_attr( $instance['hide_if_empty'] );
		$text_if_empty = esc_attr( $instance['text_if_empty'] );
		$display_post_type = esc_attr( $instance['post_type'] );
		$custom_post_types = get_post_types( array( '_builtin' => false ) , 'object' );
        ?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>
		<p><label for="<?php echo $this->get_field_id('post_type') ?>"><?php _e( 'Post type to display:', 'microkids-related-posts' ); ?></label>
		<p>
		    <select name="<?php echo $this->get_field_name('post_type') ?>" id="<?php echo $this->get_field_id('post_type') ?>">
		        <option value="all"<?php if( $display_post_type == 'all' ) : ?> selected="selected"<?php endif; ?>><?php _e('All') ?></option>
		        <option value="post"<?php if( $display_post_type == 'post' ) : ?> selected="selected"<?php endif; ?>><?php _e('Posts') ?></option>
		        <option value="page"<?php if( $display_post_type == 'page' ) : ?> selected="selected"<?php endif; ?>><?php _e('Pages') ?></option>
		        <?php if( $custom_post_types ) : foreach( $custom_post_types as $post_type ) : ?>
		        <option value="<?php echo $post_type->name; ?>"<?php if( $display_post_type == $post_type->name ) : ?> selected="selected"<?php endif; ?>><?php echo $post_type->label ?></option>
		        <?php endforeach; endif; ?>
		    </select>
		</p>
		<p><label for="<?php echo $this->get_field_id('hide_if_empty') ?>-1"><?php _e('If there are no related posts:', 'microkids-related-posts' ); ?></label></p>
		<p><input type="radio" <?php checked( $instance['hide_if_empty'], '1' ); ?> name="<?php echo $this->get_field_name('hide_if_empty') ?>" value="1" id="<?php echo $this->get_field_id('hide_if_empty') ?>-1" /> <label for="<?php echo $this->get_field_id('hide_if_empty') ?>-1"><?php _e( 'Hide the entire widget', 'microkids-related-posts' ); ?></label></p>
		<p><input type="radio" <?php checked( $instance['hide_if_empty'], '0' ); ?> name="<?php echo $this->get_field_name('hide_if_empty') ?>" value="0" id="<?php echo $this->get_field_id('hide_if_empty') ?>-2" /> <label for="<?php echo $this->get_field_id('hide_if_empty') ?>-2"><?php _e( 'Show this text:', 'microkids-related-posts' ); ?>
		</label>
		<input class="widefat" id="<?php echo $this->get_field_id('text_if_empty') ?>" name="<?php echo $this->get_field_name('text_if_empty') ?>" type="text" value="<?php echo $text_if_empty; ?>" /></p>
<?php			
	}
}
?>