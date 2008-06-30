<?php
/*
Plugin Name: Microkid's Related Posts
Plugin URI: http://www.microkid.net/wordpress/related-posts/
Description: Manually add related posts
Author: Microkid
Version: 1.0
Author URI: http://www.microkid.net/

This software is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
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
	}
			
	
	function MRP_add_custom_box() {

		add_meta_box( 'MRP_sectionid', __( 'Related Posts', 'MRP_textdomain' ), 
					'MRP_inner_custom_box', 'post', 'advanced' );
	 }
		
	function MRP_inner_custom_box() {

		global $post_ID;
		
		echo '<div id="MRP_relatedposts">';
		
		echo '<label for="MRP_relatedposts_list" id="MRP_relatedposts_list_label">Related:</label>';
		echo '<ul id="MRP_relatedposts_list">';
		
		if( $post_ID ) {
		
			if( $related_posts = MRP_get_related_posts( $post_ID ) ) {
			
				foreach( $related_posts as $related_post_id => $related_post_title ) {
				
					echo '<li id="related-post-'.$related_post_id.'"><span>'.$related_post_title.'</span><span><a class="MRP_deletebtn" onclick="MRP_remove_relation(\'related-post-'.$related_post_id.'\')">X</a></span>';
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
	
		if( count( $_POST['MRP_related_posts'] ) ) {
		
			MRP_save_relationships( $post_id, $_POST['MRP_related_posts'] );
		
		}
	}
	
	function MRP_get_related_posts( $post_id ) {
	
		global $wpdb;
		
		$query = "SELECT ".$wpdb->prefix."post_relationships.post1_id, ".$wpdb->prefix."post_relationships.post2_id FROM ".$wpdb->prefix."post_relationships WHERE ".$wpdb->prefix."post_relationships.post1_id = $post_id OR ".$wpdb->prefix."post_relationships.post2_id = $post_id";
		$results = $wpdb->get_results( $query );
		
		
		// If anyone has any bright ideas on a better solution for the following,
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
	
	function MRP_save_relationships( $post_id, $related_posts ) {
	
		global $wpdb;
		
		// First delete the relationships that were there before
		$ids_string = "";
		foreach( $related_posts as $related_posts_id ) {
			$ids_string .= "$related_posts_id, ";
		}
		$ids_string = substr( 0, -2, $ids_string );
		$query = "DELETE FROM ".$wpdb->prefix."post_relationships WHERE post1_id = $post_id OR post2_id = $post_id";
		$result = $wpdb->query( $query ); 
		
		// Now add/update the relations
		foreach( $related_posts as $related_post ) {
		
			$related_post = (int) $related_post;
			$query = "INSERT INTO ".$wpdb->prefix."post_relationships VALUES( $post_id, $related_post )";
			$result = $wpdb->query( $query );
			
		}
	
	}
	
	function MRP_delete_relationships( $post_id ) {
	
		global $wpdb;
		$query = "DELETE FROM ".$wpdb->prefix."post_relationships WHERE post1_id = $post_id OR post2_id = $post_id";
		$delete = $wpdb->query( $query );
	
	}
	
	function MRP_show_related_posts() {
		
		global $post;
		
		if( $post ) {
		
			if( $related_posts = MRP_get_related_posts( $post->ID ) ) {
				$related_posts_html = '<ul class="related-posts-list">';
				foreach( $related_posts as $related_post_id => $related_post_title  ) {
					$related_posts_html .= '<li><a href="'.get_permalink( $related_post_id ).'">'.$related_post_title.'</a></li>';
				}
				$related_posts_html .= "</ul>";
				
				echo $related_posts_html;
					
			}
			else {
		
				echo '<p class="no-related-posts">None</p>';
		
			}
			
		}
	}
	
	function MRP_load_includes() {
		
		echo '<script type="text/javascript" src="'.get_option('siteurl').'/wp-content/plugins/microkids-related-posts/microkids-related-posts.js"></script>'; 
		echo '<link rel="stylesheet" type="text/css" href="'.get_option('siteurl').'/wp-content/plugins/microkids-related-posts/microkids-related-posts.css" />';
	
	}
	
	register_activation_hook( __FILE__, 'MRP_activate' );
	add_action('admin_menu', 'MRP_add_custom_box');
	add_action('save_post', 'MRP_save_postdata');
	add_action('admin_head','MRP_load_includes');
	add_action("delete_post", "MRP_delete_relationships");
?>