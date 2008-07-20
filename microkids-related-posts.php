<?php
/*
Plugin Name: Microkid's Related Posts
Plugin URI: http://www.microkid.net/wordpress/related-posts/
Description: Manually add related posts
Author: Microkid
Version: 2.1
Author URI: http://www.microkid.net/

This software is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/


	/*------------
		When the plugin is activated, check
		if the database table already exists,
		otherwise create it
	*/
	function MRP_activate() {

		global $wpdb;
		
		$query = "SHOW TABLES LIKE '".$wpdb->prefix."post_relationships'";
		
		if( !count( $wpdb->get_results( $query ) ) ) {
			
			$query = "CREATE TABLE ".$wpdb->prefix."post_relationships (
 						post1_id bigint(20) unsigned NOT NULL,
						post2_id bigint(20) unsigned NOT NULL,
						PRIMARY KEY  (post1_id,post2_id)
					)";
										
			$create = $wpdb->query( $query );
			
		}
		
			// Create default settings
		if( !get_option("MRP_options") ) {

			$defaults = array();
			$defaults['display_auto'] = 1;
			$defaults['title'] = "Related Posts";
			$defaults['header_element'] = "h2";
			$defaults['hide_if_empty'] = 0;
			$defaults['text_if_empty'] = "None";
			
			update_option("MRP_options", $defaults );

 		}
 		
 		if( !get_option("MRP_widget_options") ) {

			$defaults = array();
			$defaults['title'] = "Related Posts";
			$defaults['hide_if_empty'] = 0;
			$defaults['text_if_empty'] = "None";
			
			update_option("MRP_widget_options", $defaults );

 		}
	}
	
	
	/*------------
		Adds the related posts form to the
		Advances Options on the post write/edit page
	*/
	function MRP_add_custom_box() {
		add_meta_box( 'MRP_sectionid', __( 'Related Posts', 'MRP_textdomain' ), 'MRP_inner_custom_box', 'post', 'normal' );
		add_meta_box( 'MRP_sectionid', __( 'Related Posts', 'MRP_textdomain' ), 'MRP_inner_custom_box', 'page', 'normal' );
	 }
	function MRP_load_includes() {
		
		echo '<script type="text/javascript" src="'.get_option('siteurl').'/wp-content/plugins/microkids-related-posts/microkids-related-posts.js"></script>'; 
		echo '<link rel="stylesheet" type="text/css" href="'.get_option('siteurl').'/wp-content/plugins/microkids-related-posts/microkids-related-posts.css" />';
	
	}
	function MRP_inner_custom_box() {

		global $post_ID;
		
		echo '<div id="MRP_relatedposts">';
		
		echo '<label for="MRP_relatedposts_list" id="MRP_relatedposts_list_label">Related:</label>';
		echo '<ul id="MRP_relatedposts_list">';
		
		if( $post_ID ) {
		
			if( $related_posts = MRP_get_related_posts( $post_ID ) ) {
			
				foreach( $related_posts as $related_post_id => $related_post_title ) {
				
					echo '<li id="related-post-'.$related_post_id.'"><span>'.$related_post_title.'</span><span><a class="MRP_deletebtn" onclick="MRP_remove_relationship(\'related-post-'.$related_post_id.'\')">X</a></span>';
					echo '<input type="hidden" name="MRP_related_posts[]" value="'.$related_post_id.'" /></li>';
				
				}			
			
			}
		
		}
		echo '</ul>';
		echo '</div>';
		echo '<div id="MRP_add_related_posts"><label for="MRP_search" id="MRP_search_label">Search posts:</label> <input type="text" id="MRP_search" name="MRP_search" value="" size="16" />';
		echo '<div id="MRP_results" class="ui-tabs-panel"></div></div>';
		
		echo '<input type="hidden" name="MRP_noncename" id="MRP_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
		echo '</div>';
	}
	
	/*------------
		Save related posts data when post is saved
	*/
	function MRP_save_postdata( $post_id ) {
	
		if ( !wp_verify_nonce( $_POST['MRP_noncename'], plugin_basename(__FILE__) )) {
			return $post_id;
		}
	
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ))
			return $post_id;
		} else {
		if ( !current_user_can( 'edit_post', $post_id ))
			return $post_id;
		}
			// Do not create a relationship with the revisions in WP 2.6
		if( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;	
		}
		
		MRP_save_relationships( $post_id, $_POST['MRP_related_posts'] );
		
	}
	
	/*------------
		Save relations
	*/
	function MRP_save_relationships( $post_id, $related_posts ) {
	
		global $wpdb;
		
			// First delete the relationships that were there before
		MRP_delete_relationships( $post_id ); 
		
			// Now add/update the relations
		if( $related_posts ) {
			foreach( $related_posts as $related_post ) {
			
				$related_post = (int) $related_post;
				$query = "INSERT INTO ".$wpdb->prefix."post_relationships VALUES( $post_id, $related_post )";
				$result = $wpdb->query( $query );
				
			}
		}	
	}
	/*------------
		Delete relations
	*/
	function MRP_delete_relationships( $post_id ) {
	
		global $wpdb;
		$query = "DELETE FROM ".$wpdb->prefix."post_relationships WHERE post1_id = $post_id OR post2_id = $post_id";
		$delete = $wpdb->query( $query );
	
	}
	
	
	/*------------
		Returns an array in this format:
		$related_posts[related_post_id] => related_post_title
	*/
	function MRP_get_related_posts( $post_id ) {
	
		global $wpdb;
		
		$query = "SELECT ".$wpdb->prefix."post_relationships.post1_id, ".$wpdb->prefix."post_relationships.post2_id FROM ".$wpdb->prefix."post_relationships WHERE ".$wpdb->prefix."post_relationships.post1_id = $post_id OR ".$wpdb->prefix."post_relationships.post2_id = $post_id ORDER BY post2_id DESC";
		$results = $wpdb->get_results( $query );
				
			// If anyone out there has any bright ideas on a better solution for the following,
			// perhaps with a JOIN, please let me know: microkid.net@gmail.com
		if( $results ) {
			$related_posts = array();
			foreach( $results as $result ) {
				if( $result->post1_id == $post_id ) {
					$query = "SELECT ID, post_title FROM $wpdb->posts WHERE ID = $result->post2_id LIMIT 1";
					$result = $wpdb->get_row( $query );
					$related_posts[$result->ID] = $result->post_title;
				}
				else {
					$query = "SELECT ID, post_title FROM $wpdb->posts WHERE ID = $result->post1_id LIMIT 1";
					$result = $wpdb->get_row( $query );
					$related_posts[$result->ID] = $result->post_title;
				}					
			}
			return $related_posts;
		}
		
		return false;
	}
	
	/*------------
		Displays a <ul> list of the related posts
		inside a container <div id="related-posts">
	*/
	function MRP_show_related_posts() {
		
		global $post;
		
		if( $post ) {
			
			echo MRP_get_related_posts_html( $post->ID );
						
		}
	}
	
	
	
	/*------------
		Called with filter "the_content".
		Automatically adds related posts
		to the bottom of the post content
		(can be controlled through plugin settings)
	*/
	function MRP_auto_related_posts( $content ) {
	
		if( is_single() || is_page() ) {
		
			$options = get_option("MRP_options");
			
			if( $options['display_auto'] ) {
	
				global $post;
				
				if( $post ) {
					
					$related_posts_html = MRP_get_related_posts_html( $post->ID );
					
				}			
				
				$content .= $related_posts_html;
			}
		}
		return $content;
	}
		
		
		
	/*------------
		This returns the HTML for the list of
		related posts
	*/
	function MRP_get_related_posts_html( $post_id ) {
	
		$options = get_option("MRP_options");
		
		$related_posts = MRP_get_related_posts( $post_id );
				
		if( $related_posts ) {
		
			$output = "<div id=\"related-posts\">\n";
			$output .= "<".$options['header_element'].">".$options['title']."</".$options['header_element'].">\n";
		  
			$output .= "<ul>\n";

			foreach( $related_posts as $related_post_id => $related_post_title  ) {
				$output .= "<li><a href=\"".get_permalink( $related_post_id )."\" title=\"$related_post_title\">".$related_post_title."</a></li>\n";
			}
			
			$output .= "</ul></div>\n";
			
		}
		else {

			if( !$options['hide_if_empty'] ) {
			
				$output = "<div id=\"related-posts\">\n";
				$output .= "<".$options['header_element'].">".$options['title']."</".$options['header_element'].">\n";

				$output .= "<p>".$options['text_if_empty']."</p>\n";
				
				$output .= "</div>\n";
			}
		}
		return $output;
	}
	
	
	/*------------
		Widget functions 
	*/

	
	function MRP_widget( $args ) {
	
		if( is_single() ) {
		
			global $post;
			
			$options = get_option("MRP_widget_options");
		
			extract( $args );
		 
			$related_posts = MRP_get_related_posts( $post->ID );
					
			if( $related_posts ) {
			
				echo $before_widget;
				echo "<div id=\"related-posts-widget\">\n";
				echo "<h2>".$options['title']."</h2>\n";
				echo "<ul>\n";
	
				foreach( $related_posts as $related_post_id => $related_post_title  ) {
					echo "<li><a href=\"".get_permalink( $related_post_id )."\" title=\"$related_post_title\">".$related_post_title."</a></li>\n";
				}
				
				echo "</ul></div>";
				
				echo $after_widget;
				
			}
			else {
			
				if( !$options['hide_if_empty'] ) {
				
					echo $before_widget;
				
					echo "<div id=\"related-posts\">\n";					
					echo "<h2>".$options['title']."</h2>\n";
					echo "<p>".$options['text_if_empty']."</p>\n";
					echo "</div>\n";
					
					echo $after_widget;
					
				}
			}
		}
	}
	function MRP_widget_control() {
	
		$options = $new_options = get_option('MRP_widget_options');
	
		if ( $_POST['MRP_submit'] ) {
		
			$options['title'] = strip_tags( stripslashes( $_POST['MRP_widget_title'] ) );
			$options['hide_if_empty'] = strip_tags( stripslashes( $_POST['MRP_hide_if_empty'] ) );
			$options['text_if_empty'] = strip_tags( stripslashes( $_POST['MRP_text_if_empty'] ) );
	
			update_option('MRP_widget_options', $options);
		}
		
		$title = attribute_escape($options['title']);
		
		$hide_if_empty = attribute_escape( $options['hide_if_empty'] );
		$text_if_empty = attribute_escape( $options['text_if_empty'] );
		
	?>
		 
		<p><label for="pages-title"><?php _e('Title:'); ?></label> <input class="widefat" id="MRP_widget_title" name="MRP_widget_title" type="text" value="<?php echo $title; ?>" /></p>
		<p><label for=""><?php _e('If there are no related posts:'); ?></label></p>
		<p><input type="radio" name="MRP_hide_if_empty" value="1" id="MRP_hide_if_empty_true"<? if($hide_if_empty) : ?> checked="checked"<? endif; ?> /> <label for="MRP_hide_if_empty_true">Hide the entire widget</label></p>
		<p><input type="radio" name="MRP_hide_if_empty" value="0" id="MRP_hide_if_empty_false"<? if(!$hide_if_empty) : ?>checked="checked"<? endif; ?> /> <label for="MRP_hide_if_empty_false">Show this text:</label>
		
		<input class="widefat" id="MRP_text_if_empty" name="MRP_text_if_empty" type="text" value="<?php echo $text_if_empty; ?>" /></p>
		<input type="hidden" id="MRP_submit" name="MRP_submit" value="1" />
	<?php
	}
	
	function MRP_load_widget() {
	
		$widget_ops = array('classname' => 'widget_related_posts', 'description' => __( "Include related posts in your sidebar") );
		wp_register_sidebar_widget('widget_related_posts', __('Related Posts'), 'MRP_widget', $widget_ops);
		wp_register_widget_control('widget_related_posts', __('Related Posts display options'), 'MRP_widget_control' );
		
	}
	
	
	/*------------
		Functions for the options page
	*/	
	function MRP_add_options_page() {
		add_options_page("Settings for Microkid's Related Posts", "Related Posts", "manage_options", __FILE__, "MRP_options_page");
	}
	function MRP_options_page() {
	
		$options = get_option('MRP_options');
		
		if( isset( $_POST['MRP_options_submit'] ) ) {
		 
			$new_options = array();
			$new_options['display_auto'] = attribute_escape( $_POST['MRP_display_auto'] );
			$new_options['title'] = attribute_escape( $_POST['MRP_title'] );
			$new_options['header_element'] = attribute_escape( $_POST['MRP_header_element'] );
			$new_options['hide_if_empty'] = attribute_escape( $_POST['MRP_hide_if_empty'] );
			if( isset( $_POST['MRP_text_if_empty'] ) ) {
				$new_options['text_if_empty'] = attribute_escape( $_POST['MRP_text_if_empty'] );
			}
			else {
				$new_options['text_if_empty'] = $options['text_if_empty'];
			}
			update_option('MRP_options', $new_options);
			$options = $new_options;
			echo '<div id="message" class="updated fade"><p><strong>Settings saved.</strong></div>';

		}
?>
<script type="text/javascript">
function MRP_disable_empty_text() {
	document.getElementById('MRP_text_if_empty').disabled = ( document.getElementById('MRP_hide_if_empty_true').checked ) ? "disabled" : "";
}
</script>
<div class="wrap">
<h2>Related Posts Settings</h2>
<form method="post" action="options-general.php?page=microkids-related-posts/microkids-related-posts.php">
<input type="hidden" id="_wpnonce" name="_wpnonce" value="abcab64052" />
<p>These settings let you customize the presentation of the list of related posts. If you're using a theme that supports it, you can also use the Related Posts <a href="widgets.php" title="Manage widgets">widget</a>.</p>
<table class="form-table">
<tr valign="top">
<th scope="row" style="width:300px;">Display related posts automatically underneath the post content?</th>
<td>
	<p><input name="MRP_display_auto" type="radio" id="MRP_display_true" value="1"<? if( $options['display_auto'] ) : ?> checked="checked"<? endif; ?> /> <label for="MRP_display_true">Yes</label></p>
	<p><input name="MRP_display_auto" type="radio" id="MRP_display_false" value="0"<? if( !$options['display_auto'] ) : ?>checked="checked"<? endif; ?> /> <label for="MRP_display_false">No, I will use the widget or implement the necessary PHP code in my theme file(s).</label></p>
</td>

</tr>
<tr valign="top">
<th scope="row" style="width:300px;">What title should be displayed?</th>
<td>
	<p><input name="MRP_title" type="text" id="MRP_title" value="<?=$options['title']?>" style="width:300px;" /></p>
	<p>
		<label for="MRP_header_element">Using HTML header element: </label>
		<select name="MRP_header_element" id="MRP_header_element" style="font-size: 80%">
			<option value="h1" <? if( $options['header_element'] == 'h1' ) : ?>selected="selected"<? endif; ?>>&lt;h1&gt;</option>
			<option value="h2" <? if( $options['header_element'] == 'h2' ) : ?>selected="selected"<? endif; ?>>&lt;h2&gt;</option>
			<option value="h3" <? if( $options['header_element'] == 'h3' ) : ?>selected="selected"<? endif; ?>>&lt;h3&gt;</option>
			<option value="h4" <? if( $options['header_element'] == 'h4' ) : ?>selected="selected"<? endif; ?>>&lt;h4&gt;</option>
			<option value="h5" <? if( $options['header_element'] == 'h5' ) : ?>selected="selected"<? endif; ?>>&lt;h5&gt;</option>
			<option value="h6" <? if( $options['header_element'] == 'h6' ) : ?>selected="selected"<? endif; ?>>&lt;h6&gt;</option>
		</select>
	</p>
</td>

</tr>
<tr valign="top">
<th scope="row" style="width:300px;">What should be displayed when there are no related posts?</th>
<td>
	<p><input name="MRP_hide_if_empty" type="radio" id="MRP_hide_if_empty_true" value="1"<? if( $options['hide_if_empty'] ) : ?>checked="checked"<? endif; ?> onclick="MRP_disable_empty_text()" /> <label for="MRP_hide_if_empty_true" onclick="MRP_disable_empty_text()">Nothing</label></p>
	<p>
		<input name="MRP_hide_if_empty" type="radio" id="MRP_hide_if_empty_false" value="0"<? if( !$options['hide_if_empty'] ) : ?>checked="checked"<? endif; ?> onclick="MRP_disable_empty_text()" /> <label for="MRP_hide_if_empty_false" onclick="MRP_disable_empty_text()">Show this text:</label>
		<input type="text" name="MRP_text_if_empty" id="MRP_text_if_empty" value="<?=$options['text_if_empty']?>" <? if( $options['hide_if_empty'] ) : ?>disabled="disabled"<? endif; ?> style="width:250px;" />
	</p>
</td>
</tr>
</table>
<p class="submit">
<input name="MRP_options_submit" value="Save Changes" type="submit" />
</p>
</form>
<?
	}
	 
	 
	
	/*------------
		Hooks, filters and such.
	*/
	register_activation_hook( __FILE__, 'MRP_activate' );
	
	add_action('admin_menu', 'MRP_add_custom_box');
	add_action('admin_menu', 'MRP_add_options_page');
	add_action('save_post', 'MRP_save_postdata'); 
	add_action('admin_head','MRP_load_includes');
	add_action("delete_post", "MRP_delete_relationships");
	add_action("widgets_init", "MRP_load_widget");

	add_filter('the_content', 'MRP_auto_related_posts');
	

?>