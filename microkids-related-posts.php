<?php
/*
Plugin Name: Microkid's Related Posts
Plugin URI: http://www.microkid.net/wordpress/related-posts/
Description: Display a set of manually selected related items with your posts
Author: Microkid
Version: 2.5
Author URI: http://www.microkid.net/

This software is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

		/*------------
			Include the widget class
		*/
	require( "microkids-related-posts-widget.php" );

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
			$defaults['display_reciprocal'] = 1;
			$defaults['title'] = "Related Posts";
			$defaults['header_element'] = "h2";
			$defaults['hide_if_empty'] = 0;
			$defaults['text_if_empty'] = "None";
			
			update_option("MRP_options", $defaults );

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
		
			if( $related_posts = MRP_get_related_posts( $post_ID, 1, 0 ) ) {
				
				foreach( $related_posts as $related_post ) {
				
					$post_title = $related_post->post_title;
					if( $related_post->post_type == 'page' ) {
						$post_title = "[Page] " . $post_title;
					}
					if( $related_post->post_status != 'publish' ) {
						$post_title = $post_title . ' ('.$related_post->post_status.')';
					}
					echo '<li id="related-post-'.$related_post->ID.'"><span>'.$post_title.'</span><span><a class="MRP_deletebtn" onclick="MRP_remove_relationship(\'related-post-'.$related_post->ID.'\')">X</a></span>';
					echo '<input type="hidden" name="MRP_related_posts[]" value="'.$related_post->ID.'" /></li>';
				
				}			
			
			}			
			else {
			
				echo '<li id="related-posts-replacement"><em>Use the search box below to select related posts</em></li>';
			
			}
		}
		else {
			
			echo '<li id="related-posts-replacement"><em style="color: #888">Use the search box below to select related posts</em></li>';
		
		}
		echo '</ul>';
		echo '</div>';
		echo '<div id="MRP_add_related_posts"><label for="MRP_search" id="MRP_search_label">Search posts:</label> <input type="text" id="MRP_search" name="MRP_search" value="" size="16" />';
		echo '<div id="MRP_scope"><label for="MRP_scope_1"><input type="radio" name="MRP_scope" id="MRP_scope_1" value="1">title</label> <label for="MRP_scope_2"><input type="radio" name="MRP_scope" id="MRP_scope_2" value="2">content</label> <label for="MRP_scope_3"><input type="radio" name="MRP_scope" id="MRP_scope_3" value="3" checked="checked"><strong>both</strong></label></div>';
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
			if ( !current_user_can( 'edit_page', $post_id )) {
				return $post_id;
			}
		}
		else {
			if ( !current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}
			// Do not create a relationship with the revisions in WP 2.6
		if( function_exists("wp_is_post_revision") ) {
			if( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
				return $post_id;	
			}
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
		
		$options = get_option("MRP_options");
		
		if($options['display_reciprocal']) {
			$query = "DELETE FROM ".$wpdb->prefix."post_relationships WHERE post1_id = $post_id OR post2_id = $post_id";
		}
		else {
			$query = "DELETE FROM ".$wpdb->prefix."post_relationships WHERE post1_id = $post_id";
		}
		$delete = $wpdb->query( $query );
	
	}
	
	
	/*------------
		Returns an array in this format:
		$related_posts[related_post_id] => related_post_title
	*/
	function MRP_get_related_posts( $post_id, $return_object = false, $hide_unpublished = true ) {

		global $wpdb;

		$options = get_option("MRP_options");
		
		$post_status = array( "'publish'" );
		if( current_user_can( "read_private_posts" ) ) {
			$post_status[] = "'private'";
		}
		
		$wpdb->show_errors();

		if($options['display_reciprocal']) {

			//
			// Newer, faster (and definitely more SQL like) way to fetch related postings
			// (Thanks Peter Raganitsch @ http://blog.oracleapex.at)
			// 
			$query = "SELECT * ".
				"FROM ".$wpdb->prefix."post_relationships	wpr ".
				",".$wpdb->prefix."posts					wp ".
				"WHERE wpr.post1_id = $post_id ".
				"AND wp.id = wpr.post2_id ";
			if( $hide_unpublished ) {
				$query .= "AND wp.post_status IN (".implode( ",", $post_status ).") ";
			}
			$query .= "UNION ALL ".
				"SELECT * ".
				"FROM ".$wpdb->prefix."post_relationships	wpr ".
				",".$wpdb->prefix."posts					wp ".
				"WHERE wpr.post2_id = $post_id ".
				"AND wp.id = wpr.post1_id ";
			if( $hide_unpublished ) {
				$query .= "AND wp.post_status IN (".implode( ",", $post_status ).") ";
			}
		}
		else {
			$query = "SELECT * ".
				"FROM ".$wpdb->prefix."post_relationships	wpr ".
				" JOIN ".$wpdb->prefix."posts				wp ".
				"	ON wpr.post2_id = wp.ID ".
				"WHERE wpr.post1_id = $post_id";
			if( $hide_unpublished ) {
				$query .= " AND wp.post_status IN (".implode( ",", $post_status ).") ";
			}
		}

		$results = $wpdb->get_results( $query );

		if( $results ) {
			if( $return_object ) {
				return $results;
			}
			else {
				$related_posts = array();
				foreach( $results as $result ) {
					$related_posts[$result->ID] = $result->post_title;
				}
				return $related_posts;
			}
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
		
		$related_posts = MRP_get_related_posts( $post_id, 1, 1 );
				
		if( $related_posts ) {
		
			$output = "<div id=\"related-posts\">\n";
			$output .= "<".$options['header_element'].">".$options['title']."</".$options['header_element'].">\n";
		  
			$output .= "<ul>\n";

			foreach( $related_posts as $related_post  ) {
				$output .= "<li><a href=\"".get_permalink( $related_post->ID )."\">".$related_post->post_title."</a></li>\n";
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
			Initiate shortcode feature
		*/
	function MRP_shortcode($atts) {
		global $post;
		if( $post->ID ) {
			return MRP_get_related_posts_html( $post->ID );
		}
	}
	
		/*------------
			Register the widget 
		*/
	function MRP_load_widget() {
		register_widget("wp_widget_related_posts");		
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
			$new_options['display_reciprocal'] = attribute_escape( $_POST['MRP_display_reciprocal'] );
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
<th scope="row" style="width:300px;"><p>Display related posts automatically underneath the post content?</p></th>
<td>
	<p><input name="MRP_display_auto" type="radio" id="MRP_display_true" value="1"<?php if( $options['display_auto'] ) : ?> checked="checked"<?php endif; ?> /> <label for="MRP_display_true">Yes</label></p>
	<p><input name="MRP_display_auto" type="radio" id="MRP_display_false" value="0"<?php if( !$options['display_auto'] ) : ?>checked="checked"<?php endif; ?> /> <label for="MRP_display_false">No, I will use the widget or implement the necessary PHP code in my theme file(s).</label></p>
</td>

</tr>


<tr valign="top">
<th scope="row" style="width:300px;"><p>Should related posts be reciprocal?  If so, the link will appear on both pages.  If not, it will only appear on the post/page where it was selected.</p></th>
<td>
	<p><input name="MRP_display_reciprocal" type="radio" id="MRP_reciprocal_true" value="1"<?php if( $options['display_reciprocal'] ) : ?> checked="checked"<?php endif; ?> /> <label for="MRP_reciprocal_true">Yes, include the link on both pages</label></p>
	<p><input name="MRP_display_reciprocal" type="radio" id="MRP_reciprocal_false" value="0"<?php if( !$options['display_reciprocal'] ) : ?>checked="checked"<?php endif; ?> /> <label for="MRP_reciprocal_false">No, only show the link on one page</label></p>
</td>

</tr>



<tr valign="top">
<th scope="row" style="width:300px;"><p>What title should be displayed?</p></th>
<td>
	<p><input name="MRP_title" type="text" id="MRP_title" value="<?=$options['title']?>" style="width:300px;" /></p>
	<p>
		<label for="MRP_header_element">Using HTML header element: </label>
		<select name="MRP_header_element" id="MRP_header_element" style="font-size: 80%">
			<option value="h1" <?php if( $options['header_element'] == 'h1' ) : ?>selected="selected"<?php endif; ?>>&lt;h1&gt;</option>
			<option value="h2" <?php if( $options['header_element'] == 'h2' ) : ?>selected="selected"<?php endif; ?>>&lt;h2&gt;</option>
			<option value="h3" <?php if( $options['header_element'] == 'h3' ) : ?>selected="selected"<?php endif; ?>>&lt;h3&gt;</option>
			<option value="h4" <?php if( $options['header_element'] == 'h4' ) : ?>selected="selected"<?php endif; ?>>&lt;h4&gt;</option>
			<option value="h5" <?php if( $options['header_element'] == 'h5' ) : ?>selected="selected"<?php endif; ?>>&lt;h5&gt;</option>
			<option value="h6" <?php if( $options['header_element'] == 'h6' ) : ?>selected="selected"<?php endif; ?>>&lt;h6&gt;</option>
		</select>
	</p>
</td>

</tr>
<tr valign="top">
<th scope="row" style="width:300px;"><p>What should be displayed when there are no related posts?</p></th>
<td>
	<p><input name="MRP_hide_if_empty" type="radio" id="MRP_hide_if_empty_true" value="1"<?php if( $options['hide_if_empty'] ) : ?>checked="checked"<?php endif; ?> onclick="MRP_disable_empty_text()" /> <label for="MRP_hide_if_empty_true" onclick="MRP_disable_empty_text()">Nothing</label></p>
	<p>
		<input name="MRP_hide_if_empty" type="radio" id="MRP_hide_if_empty_false" value="0"<?php if( !$options['hide_if_empty'] ) : ?>checked="checked"<?php endif; ?> onclick="MRP_disable_empty_text()" /> <label for="MRP_hide_if_empty_false" onclick="MRP_disable_empty_text()">Show this text:</label>
		<input type="text" name="MRP_text_if_empty" id="MRP_text_if_empty" value="<?=$options['text_if_empty']?>" <?php if( $options['hide_if_empty'] ) : ?>disabled="disabled"<?php endif; ?> style="width:250px;" />
	</p>
</td>
</tr>
</table>
<p class="submit">
<input name="MRP_options_submit" value="Save Changes" type="submit" />
</p>
</form>
<?php
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
	
	add_shortcode('related-posts', 'MRP_shortcode');
	
	

?>