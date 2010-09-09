<?php
/*
Plugin Name: Microkid's Related Posts
Plugin URI: http://www.microkid.net/wordpress/related-posts/
Description: Display a set of manually selected related items with your posts
Author: Microkid
Version: 3.0.1
Author URI: http://www.microkid.net/

This software is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

# Run at activation 
register_activation_hook( __FILE__, 'MRP_activate' );

# Add custom box on edit page
add_action('admin_menu', 'MRP_add_custom_box');

# Add options page
add_action('admin_menu', 'MRP_add_options_page');

# Save related posts
add_action('save_post', 'MRP_save_postdata'); 

# Include javascript and css files
add_action('admin_head','MRP_load_includes');

# Remove related posts
add_action("delete_post", "MRP_delete_relationships");

# Load widgets
add_action("widgets_init", "MRP_load_widget");

# Automatically add related posts list to post content
add_filter('the_content', 'MRP_auto_related_posts');

# Add [related-posts] shortcode support
add_shortcode('related-posts', 'MRP_shortcode');

# Load internationalization files
if( is_admin() ){
    load_plugin_textdomain( 'microkids-related-posts', WP_PLUGIN_DIR . '/' .dirname(plugin_basename(__FILE__)) . '/languages', dirname(plugin_basename(__FILE__)) . '/languages' );
}

# Include the widget class
require( "microkids-related-posts-widget.php" );

/**
 * MRP_activate - Run when the plugin is first installed and after an upgrade
 */
function MRP_activate() {
	global $wpdb;
	# Check if post_relationships table exists, if not, create it
	$query = "SHOW TABLES LIKE '".$wpdb->prefix."post_relationships'";
	if( !count( $wpdb->get_results( $query ) ) ) {
		$query = "CREATE TABLE ".$wpdb->prefix."post_relationships (
						post1_id bigint(20) unsigned NOT NULL,
					post2_id bigint(20) unsigned NOT NULL,
					PRIMARY KEY  (post1_id,post2_id)
				)";
		$create = $wpdb->query( $query );
	}
	
	$options = get_option("MRP_options");
	# Create default settings
	if( !$options ) {
		$defaults = array();
		$defaults['display_auto'] = 1;
		$defaults['display_reciprocal'] = 1;
		$defaults['title'] = "Related Posts";
		$defaults['header_element'] = "h2";
		$defaults['hide_if_empty'] = 0;
		$defaults['text_if_empty'] = "None";
		$defaults['order'] = "date_desc";
		$defaults['post_types'] = "post,page";
		if( $custom_post_types = get_post_types( array( '_builtin' => false ) ) ) {
 		    foreach( $custom_post_types as $custom_post_type ) {
 		        $defaults['post_types'] .= ",$custom_post_type";
 		    }
 		}
 		$defaults['combine_post_types'] = 1;
 		$options['hide_donate'] = 0;
        update_option( "MRP_options", $defaults );
	}
	# This occurs when upgrading to v3.0
	elseif( ! isset($options['post_types'] ) ) {
	    $options['post_types'] = "post,page";
	    foreach( get_post_types( array( '_builtin' => false ) ) as $custom_post_type ) {
	        $options['post_types'] .= ",$custom_post_type";
	    }
	    $options['combine_post_types'] = 1;
	    $options['order'] = "date_desc";
	    $options['hide_donate'] = 0;
	    update_option( "MRP_options", $options );
	}
}
	
	
/**
 * MRP_add_custom_box - Add the related posts custom box to the post add/edit screen
 */
function MRP_add_custom_box() {
	foreach( MRP_get_supported_post_types() as $post_type ) {
	    add_meta_box( 'MRP_sectionid', __( 'Related items', 'microkids-related-posts' ), 'MRP_inner_custom_box', $post_type, 'normal' );
	}
 }

/**
 * MRP_load_includes - Load admin javascript and css files
 */
function MRP_load_includes() {	
	echo '<script type="text/javascript" src="'.get_option('siteurl').'/wp-content/plugins/microkids-related-posts/microkids-related-posts.js"></script>'; 
	echo '<link rel="stylesheet" type="text/css" href="'.get_option('siteurl').'/wp-content/plugins/microkids-related-posts/microkids-related-posts.css" />';
}

/**
 * MRP_inner_custom_box - Load the insides
 */
function MRP_inner_custom_box() {
	global $post_ID;
	$post_types = MRP_get_supported_post_types();
	echo '<div id="MRP_relatedposts">';
	$n = 1; 
	echo '<div id="MRP_tabs">';
	$related_posts = array();
	# Create tabs
	foreach($post_types as $post_type) {
	    $related_posts[$post_type] = MRP_get_related_posts( $post_ID, 1, 0, $post_type );
	    $ext = "-".$n;
	    $post_type_details = array_shift(get_post_types(array('name' => $post_type), 'objects'));
	    $current = ($n==1) ? ' MRP_current_tab' : '';
	    $related_posts_count = count($related_posts[$post_type]);
	    echo '<div class="MRP_tab'.$current.'"><a href="#" rel="MRP_post_type'.$ext.'">'.__( $post_type_details->labels->name ).' (<span id="MRP_related_count'.$ext.'" class="MRP_related_count">'.$related_posts_count.'</span>)</a></div>';
	    $n++;
	}
	echo '</div>';
	$n = 1;
	# Loop through post types and create form elements for each
	foreach($post_types as $post_type) {
	    $ext = "-".$n;	    
	    $current = ($n==1) ? ' MRP_current' : '';
	    echo '<div id="MRP_post_type'.$ext.'" class="MRP_post_type'.$current.'">';
	    echo '<label id="MRP_relatedposts_list_label'.$ext.'" class="MRP_relatedposts_list_label">'.__( 'Related', 'microkids-related-posts' ).':</label>';
		echo '<ul id="MRP_relatedposts_list'.$ext.'" class="MRP_relatedposts_list">';
		if( $post_ID ) {
			if( count($related_posts[$post_type]) > 0 ) {
				foreach( $related_posts[$post_type] as $related_post ) {
			        $post_title = $related_post->post_title;
					if( $related_post->post_status != 'publish' ) {
						$post_title = $post_title . ' ('.$related_post->post_status.')';
					}
					echo '<li id="related-post-'.$related_post->ID.'"><span>'.$post_title.'</span><span><a class="MRP_deletebtn" onclick="MRP_remove_relationship(\'related-post-'.$related_post->ID.'\')">X</a></span>';
					echo '<input type="hidden" name="MRP_related_posts[]" value="'.$related_post->ID.'" /></li>';
				}			
			}			
			else {
				echo '<li id="MRP_related_posts_replacement'.$ext.'" class="MRP_related_posts_replacement"><em>'.__( 'Use the search box below to select related items', 'microkids-related-posts' ).'</em></li>';
			}
		}
		else {
			echo '<li id="MRP_related_posts_replacement'.$ext.'" class="MRP_related_posts_replacement"><em>'.__( 'Use the search box below to select related items', 'microkids-related-posts' ).'</em></li>';
		}
		echo '</ul>';
        
        echo '<input type="hidden" name="MRP_post_type_name'.$ext.'" id="MRP_post_type_name'.$ext.'" value="'.$post_type.'"/>';
		echo '<div id="MRP_add_related_posts'.$ext.'" class="MRP_add_related_posts"><label for="MRP_search" id="MRP_search_label'.$ext.'" class="MRP_search_label">'.__( 'Search items', 'microkids-related-posts' ).':</label> <input type="text" id="MRP_search'.$ext.'" class="MRP_search" name="MRP_search'.$ext.'" value="" size="16" />';
		echo '<div id="MRP_scope'.$ext.'" class="MRP_scope"><label for="MRP_scope_1'.$ext.'"><input type="radio" name="MRP_scope'.$ext.'" id="MRP_scope_1'.$ext.'" class="MRP_scope_1" value="1">'.__( 'Title', 'microkids-related-posts' ).'</label> <label for="MRP_scope_2'.$ext.'"><input type="radio" name="MRP_scope'.$ext.'" id="MRP_scope_2'.$ext.'" class="MRP_scope_2" value="2">'.__( 'Content', 'microkids-related-posts' ).'</label> <label for="MRP_scope_3'.$ext.'"><input type="radio" name="MRP_scope'.$ext.'" id="MRP_scope_3'.$ext.'" class="MRP_scope_3" value="3" checked="checked"><strong>'.__( 'Both', 'microkids-related-posts' ).'</strong></label></div>';
		echo '<div id="MRP_loader'.$ext.'" class="MRP_loader">&nbsp;</div>';
		echo '<div id="MRP_results'.$ext.'" class="ui-tabs-panel MRP_results"></div></div>';
		echo '</div>';
		$n++;
	}
	echo '<input type="hidden" name="MRP_noncename" id="MRP_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
	echo '</div>';
}

/**
 * MRP_save_postdata - Prepare to save the selected relations
 *
 * @param int   $post_id - The id of the post being saved
 */
function MRP_save_postdata( $post_id ) {
    if( !isset($_POST['MRP_noncename'])) {
        return $post_id;
    }
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
    # Do not create a relationship with the revisions and autosaves in WP 2.6
	if( function_exists("wp_is_post_revision") ) {
		if( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;	
		}
	}
	MRP_save_relationships( $post_id, $_POST['MRP_related_posts'] );
}

/**
 * MRP_save_relationships - Save the relations
 *
 * @param int   $post_id - The id of the post being saved
 * @param array $related_posts - A list of post_id's
 */
 function MRP_save_relationships( $post_id, $related_posts ) {
	global $wpdb;
    # First delete the relationships that were there before
	MRP_delete_relationships( $post_id ); 
    # Now add/update the relations
	if( $related_posts ) {
		foreach( $related_posts as $related_post ) {
			$related_post = (int) $related_post;
			$query = "INSERT INTO ".$wpdb->prefix."post_relationships VALUES( $post_id, $related_post )";
			$result = $wpdb->query( $query );
		}
	}	
}

/**
 * MRP_delete_relationships - Delete all relationships for a post
 *
 * @param int   $post_id - The id of the post for which the relationships are deleted
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


/**
 * MRP_get_related_posts - Get the related posts for a post
 *
 * @param int       $post_id - The id of the post
 * @param bool      $return_object - Whether to return the related posts as an object
 *                  If false it will return the posts as an array $related_posts[related_post_id] => related_post_title
 * @param bool      $hide_unpublished - When false drafts will be included
 * @param string    $post_type - The post type of the related posts to return i.e. post, page, or any custom post types
 *                  When null all post types will be returned
*/
function MRP_get_related_posts( $post_id, $return_object = false, $hide_unpublished = true, $post_type = null ) {
	global $wpdb;
	$options = get_option("MRP_options");
	$post_status = array( "'publish'" );
	# Display private posts for users with the correct permissions
	if( current_user_can( "read_private_posts" ) ) {
		$post_status[] = "'private'";
	}
	# Generate order SQL based on plugin settings
	$order = " ORDER BY ";
	switch( $options['order'] ) {
	    case 'random' :
	        $order .= " RAND() ";
	    break;
	    case 'date_desc' :
	        $order .= " post_date DESC ";
	    break;
	    case 'date_asc' :
	        $order .= " post_date ASC ";
	    break;
	    case 'title_desc' :
	        $order .= " post_title DESC ";
	    break;
	    case 'title_asc' :
	        $order .= " post_title ASC ";
	    break;
	}
	if($options['display_reciprocal']) {
		# Reciprocal query by Peter Raganitsch @ http://blog.oracleapex.at)
		$query = "SELECT * ".
			"FROM ".$wpdb->prefix."post_relationships	wpr ".
			",".$wpdb->prefix."posts					wp ".
			"WHERE wpr.post1_id = $post_id ".
			"AND wp.id = wpr.post2_id ";
		# Hide unpublished?
		if( $hide_unpublished ) {
			$query .= " AND wp.post_status IN (".implode( ",", $post_status ).") ";
		}
		# Show only specified post type?
		if( isset( $post_type ) ) {
		    if( is_array( $post_type ) ) {
		        $query .= " AND wp.post_type IN (".implode( ",", $post_type ).") ";
		    }
		    else {
		        $query .= " AND wp.post_type = '$post_type' ";
		    }
		}
		$query .= "UNION ALL ".
			"SELECT * ".
			"FROM ".$wpdb->prefix."post_relationships	wpr ".
			",".$wpdb->prefix."posts					wp ".
			"WHERE wpr.post2_id = $post_id ".
			"AND wp.id = wpr.post1_id ";
		# Hide unpublished?
		if( $hide_unpublished ) {
			$query .= "AND wp.post_status IN (".implode( ",", $post_status ).") ";
		}
		# Show only specified post type?
		if( isset( $post_type ) ) {
		    if( is_array( $post_type ) ) {
		        $query .= " AND wp.post_type IN (".implode( ",", $post_type ).") ";
		    }
		    else {
		        $query .= " AND wp.post_type = '$post_type' ";
		    }
		}
		# Add order SQL
		$query .= $order;
	}
	# Not reciprocal
	else {
		$query = "SELECT * ".
			"FROM ".$wpdb->prefix."post_relationships	wpr ".
			" JOIN ".$wpdb->prefix."posts				wp ".
			"	ON wpr.post2_id = wp.ID ".
			"WHERE wpr.post1_id = $post_id";
		# Hide unpublished?
		if( $hide_unpublished ) {
			$query .= " AND wp.post_status IN (".implode( ",", $post_status ).") ";
		}
		# Show only specified post type?
		if( isset( $post_type ) ) {
		    if( is_array( $post_type ) ) {
		        $query .= " AND wp.post_type IN (".implode( ",", $post_type ).") ";
		    }
		    else {
		        $query .= " AND wp.post_type = '$post_type' ";
		    }
		}
		$query .= $order;
	}
    # Run query
	$results = $wpdb->get_results( $query );
	if( $results ) {
		if( $return_object ) {
		    # Return the complete results set as an object
			return $results;
		}
		else {
		    # Create array (legacy)
			$related_posts = array();
			foreach( $results as $result ) {
				$related_posts[$result->ID] = $result->post_title;
			}
			return $related_posts;
		}
	}
	return null;
}

/**
 * MRP_show_related_posts - Display the related posts for the current post
 */
function MRP_show_related_posts() {
	global $post;
	if( $post ) {
		echo MRP_get_related_posts_html( $post->ID );	
	}
}


/**
 * MRP_auto_related_posts - Called by the_content filter, automatically add the
 *                          related posts at the bottom of the content
 *
 * @param string    $content - The text/content of the post
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
	
/**
 * MRP_get_related_posts_html - Generate the related posts HTML for a post and return it as a string
 *
 * @param int       $post_id - The id of the post
 * @param bool      $hide_unpublished - When false drafts will be included
 * @param string    $post_type - The post type of the related posts to return i.e. post, page, or any custom post types
 *                  When null all post types will be returned
 */		
function MRP_get_related_posts_html( $post_id, $hide_unpublished = true, $post_type = null ) {
    # Get plugin settings
    $options = get_option("MRP_options");
    # Get the supported post types (selected in plugin settings)
	$supported_post_types = MRP_get_supported_post_types();
	if(!$supported_post_types) {
	    return false;
	}
	$related_posts = array();
	# If only related posts for specified post type are needed
	if( isset( $post_type ) ) {
	    $related_posts[$post_type] = MRP_get_related_posts( $post_id, true, $hide_unpublished, $post_type );
	}
	# If we need related posts from all post types
	else {
	    # First get the entire set of related posts for all post types
	    $all_related_posts = MRP_get_related_posts( $post_id, true, $hide_unpublished, null );
	    # If related posts should be displayed as one list
	    if( $options['combine_post_types'] == 1 ) {
            $related_posts['MRP_all'] = $all_related_posts;
		}
		# If related posts should be grouped by post types
		else {
		    if( $all_related_posts ) {
		        foreach( $all_related_posts as $related_post ) {
                    $related_posts[$related_post->post_type][] = $related_post;
		        }
		    }
		}
	}
	# Start HTML output
	$output = "<div id=\"related-posts\">\n";
	# Loop through different post types
    foreach( $related_posts as $post_type => $post_type_related_posts ) {
        # This filters %posttype% from the title
        $title = MRP_get_title( $options['title'], $post_type );
        if( count( $post_type_related_posts ) ) {
			$output .= "<div id=\"related-posts-$post_type\" class=\"related-posts-type\">\n";
			# Create the title with the selected HTML header
			$output .= "<".$options['header_element'].">".$title."</".$options['header_element'].">\n";
			$output .= "<ul>\n";
			# Add related posts
			foreach( $post_type_related_posts as $related_post ) {
				$output .= "<li><a href=\"".get_permalink( $related_post->ID )."\">".$related_post->post_title."</a></li>\n";
			}
			$output .= "</ul></div>\n";
	    }
	    # If there are no related posts for this post type
	    else {
			if( !$options['hide_if_empty'] ) {
			    $output .= "<div id=\"related-posts-$post_type\" class=\"related-posts-type\">\n";
				$output .= "<".$options['header_element'].">".$title."</".$options['header_element'].">\n";
				$output .= "<p>".$options['text_if_empty']."</p>\n";
				$output .= "</div>";
			}
			else {
			    # Show nothing
			    return "";
			}
		}
	}
	$output .= "</div>";
	return $output;
}

/**
 * MRP_get_title - Replaces %posttype% from the title with the name/label of the post type
 *
 * @param string    $title - The title as set on the plugin options page
 * @param string    $post_type_name - The name/label of the post type
 */
function MRP_get_title( $title, $post_type_name = null ) {
    if( $post_type_name != 'MRP_all' ) {
        $post_type = array_shift( get_post_types( array( 'name' => $post_type_name ), 'object' ) );
        $title = str_replace( '%posttype%', __( $post_type->label ), $title );
    }
    else {
        $title = str_replace( '%posttype%', __( 'Posts' ), $title );
    }
    return $title;
}

/**
 * MRP_shortcode - Add [related-post] shortcode support through standard Worpress API
 *
 * @param array    $atts - An array of attributes given to the shortcode i.e. [related-posts posttype=page]
 */
function MRP_shortcode( $atts ) {
	global $post;
	$post_type = null;
	if( $post->ID ) {
	    if( $atts ) {
	        $post_type = esc_attr( $atts['posttype'] );
	    }
		return MRP_get_related_posts_html( $post->ID, true, $post_type );
	}
}

/**
 * MRP_load_widget - Register the Related Posts widget
 */
function MRP_load_widget() {
	register_widget("wp_widget_related_posts");		
}


/**
 * MRP_add_options_page - Generate the options page
 */
function MRP_add_options_page() {
	add_options_page("Settings for Microkid's Related Posts", "Related Posts", "manage_options", __FILE__, "MRP_options_page");
}

/**
 * MRP_get_supported_post_types - Get the post types that can display and be related posts
 *                                Thanks to Michael Girouard at Migimedia for contributing
 *
 * @param bool  $details -  Whether to return the entire object for each post type, 
 *                          if false only an array of names will be returned
 */
function MRP_get_supported_post_types($details = false) {
    $options = get_option('MRP_options');
    $post_types = explode(',', $options['post_types']); 
    if (false === $details) {
        return $post_types;
    }
    $details = array();
    foreach ($post_types as $post_type) {
        $post_type_details = get_post_types(array('name' => $post_type), 'object');
        $details[$post_type] = $post_type_details[$post_type];
    }
    return $details;
}

/**
 * MRP_options_page - Generate the plugin options page
 *                                Thanks to Michael Girouard at Migimedia for contributing
 */
function MRP_options_page() {
    # Get current options
	$options = get_option('MRP_options');
	# Save new settings 
	if( isset( $_POST['MRP_options_submit'] ) ) {
	    if( ! empty($_POST['MRP_custom_post_types'] ) ) {
	        $_POST['MRP_custom_post_types'] = array_map( 'trim', $_POST['MRP_custom_post_types'] );
            $_POST['MRP_custom_post_types'] = implode( ',', $_POST['MRP_custom_post_types'] );
	    }
	    # Create array with new settings
	    $new_options = array();
		$new_options['display_auto'] = (int) $_POST['MRP_display_auto'];
		$new_options['display_reciprocal'] = (int) $_POST['MRP_display_reciprocal'];
		$new_options['title'] = esc_attr( $_POST['MRP_title'] );
		$new_options['header_element'] = esc_attr( $_POST['MRP_header_element'] );
		$new_options['hide_if_empty'] = (int) $_POST['MRP_hide_if_empty'];
		$new_options['order'] = esc_attr( $_POST['MRP_order'] );
		if( isset($_POST['MRP_custom_post_types'])) {
            $new_options['post_types'] = esc_attr( $_POST['MRP_custom_post_types'] );
        }
        else {
            $new_options['post_types'] = "";
        }
		if( isset( $_POST['MRP_text_if_empty'] ) ) {
			$new_options['text_if_empty'] = esc_attr( $_POST['MRP_text_if_empty'] );
		}
		else {
			$new_options['text_if_empty'] = $options['text_if_empty'];
		}
		$new_options['combine_post_types'] = (int) $_POST['MRP_combine'];
		$new_options['hide_donate'] = $options['hide_donate'];
		# Save options in wp_options tabl
		update_option('MRP_options', $new_options);
		$options = $new_options;
		echo '<div id="message" class="updated fade"><p><strong>'.__( 'Settings saved', 'microkids-related-posts' ).'</strong></div>';
	}
	# Hide the donation message
	elseif( isset($_GET['hide_donate'] ) ) {
	    $new_options = $options;
	    $new_options['hide_donate'] = 1;
	    update_option( 'MRP_options', $new_options );
	    echo '<div id="message" class="updated fade"><p><strong>'.__( 'Donation message hidden', 'microkids-related-posts' ).'</strong></div>';
	}
?>
<script type="text/javascript">
function MRP_disable_empty_text() {
	document.getElementById('MRP_text_if_empty').disabled = ( document.getElementById('MRP_hide_if_empty_true').checked ) ? "disabled" : "";
}
</script>
<div class="wrap">
    <h2><?php _e( 'Related posts settings', 'microkids-related-posts' ); ?></h2>
    <form method="post" action="options-general.php?page=microkids-related-posts/microkids-related-posts.php">
        <input type="hidden" id="_wpnonce" name="_wpnonce" value="abcab64052" />
        <p><?php printf( __('These settings let you customize the presentation of the list of related posts. If you\'re using a theme that supports it, you can also use the Related Posts <a%s>widget</a>.', 'microkids-related-posts' ), ' href="widgets.php"' ); ?></p>
        <table class="form-table">
        <tr valign="top">
            <th scope="row" style="width:300px;"><p><?php _e( 'Display related posts automatically underneath the post content?', 'microkids-related-posts' ); ?></p></th>
            <td>
            	<p><input name="MRP_display_auto" type="radio" id="MRP_display_true" value="1"<?php if( $options['display_auto'] ) : ?> checked="checked"<?php endif; ?> /> <label for="MRP_display_true"><?php _e( 'Yes', 'microkids-related-posts' ); ?></label></p>
            	<p><input name="MRP_display_auto" type="radio" id="MRP_display_false" value="0"<?php if( !$options['display_auto'] ) : ?>checked="checked"<?php endif; ?> /> <label for="MRP_display_false"><?php _e( 'No, I will use the widget, the shortcode or implement the necessary PHP code in my theme file(s).', 'microkids-related-posts' ); ?></label></p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" style="width:300px;"><p><?php _e( 'Should related posts be reciprocal? If so, the link will appear on both pages. If not, it will only appear on the post/page where it was selected.', 'microkids-related-posts' ); ?></p></th>
            <td>
            	<p><input name="MRP_display_reciprocal" type="radio" id="MRP_reciprocal_true" value="1"<?php if( $options['display_reciprocal'] ) : ?> checked="checked"<?php endif; ?> /> <label for="MRP_reciprocal_true"><?php _e( 'Yes, include the link on both pages', 'microkids-related-posts' ); ?></label></p>
            	<p><input name="MRP_display_reciprocal" type="radio" id="MRP_reciprocal_false" value="0"<?php if( !$options['display_reciprocal'] ) : ?>checked="checked"<?php endif; ?> /> <label for="MRP_reciprocal_false"><?php _e( 'No, only show the link on one page', 'microkids-related-posts' ); ?></label></p>
            </td>
        </tr>
        <tr valign="top">
        <th scope="row" style="width:300px;"><p><?php _e( 'What title should be displayed?', 'microkids-related-posts' ); ?></p></th>
        <td>
        	<p><input name="MRP_title" type="text" id="MRP_title" value="<?=$options['title']?>" style="width:300px;" /></p>
        	<p>
        		<label for="MRP_header_element"><?php _e( 'Using HTML header element' , 'microkids-related-posts'); ?>: </label>
        		<select name="MRP_header_element" id="MRP_header_element" style="font-size: 80%">
        			<option value="h1" <?php if( $options['header_element'] == 'h1' ) : ?>selected="selected"<?php endif; ?>>&lt;h1&gt;</option>
        			<option value="h2" <?php if( $options['header_element'] == 'h2' ) : ?>selected="selected"<?php endif; ?>>&lt;h2&gt;</option>
        			<option value="h3" <?php if( $options['header_element'] == 'h3' ) : ?>selected="selected"<?php endif; ?>>&lt;h3&gt;</option>
        			<option value="h4" <?php if( $options['header_element'] == 'h4' ) : ?>selected="selected"<?php endif; ?>>&lt;h4&gt;</option>
        			<option value="h5" <?php if( $options['header_element'] == 'h5' ) : ?>selected="selected"<?php endif; ?>>&lt;h5&gt;</option>
        			<option value="h6" <?php if( $options['header_element'] == 'h6' ) : ?>selected="selected"<?php endif; ?>>&lt;h6&gt;</option>
        		</select>
        	</p>
        	<p style="-moz-border-radius: 4px;-webkit-border-radius: 4px;border-radius: 4px;margin: 0; background: #eee; font-size: 11px;width: 40%; padding: 5px;"><?php _e( '<strong>Note:</strong> if you choose to display different post types as seperate lists, you can place <strong>%posttype%</strong> in the title to display the label of the post type.', 'microkids-related-posts' ); ?></p>
        </td>
        </tr>
        <tr valign="top">
        <th scope="row" style="width:300px;"><p><?php _e( 'What should be displayed when there are no related posts?', 'microkids-related-posts' ); ?></p></th>
        <td>
        	<p><input name="MRP_hide_if_empty" type="radio" id="MRP_hide_if_empty_true" value="1"<?php if( $options['hide_if_empty'] ) : ?>checked="checked"<?php endif; ?> onclick="MRP_disable_empty_text()" /> <label for="MRP_hide_if_empty_true" onclick="MRP_disable_empty_text()"><?php _e( 'Nothing', 'microkids-related-posts' ); ?></label></p>
        	<p>
        		<input name="MRP_hide_if_empty" type="radio" id="MRP_hide_if_empty_false" value="0"<?php if( !$options['hide_if_empty'] ) : ?>checked="checked"<?php endif; ?> onclick="MRP_disable_empty_text()" /> <label for="MRP_hide_if_empty_false" onclick="MRP_disable_empty_text()"><?php _e( 'Show this text', 'microkids-related-posts' ); ?>:</label>
        		<input type="text" name="MRP_text_if_empty" id="MRP_text_if_empty" value="<?=$options['text_if_empty']?>" <?php if( $options['hide_if_empty'] ) : ?>disabled="disabled"<?php endif; ?> style="width:250px;" />
        	</p>
        </td>
        </tr>
        </tr>
        <tr valign="top">
        <th scope="row" style="width:300px;"><p><?php _e( 'In what order would you like to display the related posts?', 'microkids-related-posts' ); ?></p></th>
        <td>
        	<p>
        	    <select name="MRP_order" id="MRP_order">
        	        <option value="date_desc"<?php if($options['order'] == 'date_desc') : ?> selected="selected"<?php endif; ?>><?php _e( 'By date, new to old', 'microkids-related-posts' ); ?></option>
        	        <option value="date_asc"<?php if($options['order'] == 'date_asc') : ?> selected="selected"<?php endif; ?>><?php _e( 'By date, old to new', 'microkids-related-posts' ); ?></option>
        	        <option value="title_asc"<?php if($options['order'] == 'title_asc') : ?> selected="selected"<?php endif; ?>><?php _e( 'Alphabetical', 'microkids-related-posts' ); ?></option>
        	        <option value="title_desc"<?php if($options['order'] == 'title_desc') : ?> selected="selected"<?php endif; ?>><?php _e( 'Reverse alphabetical', 'microkids-related-posts' ); ?></option>
        	        <option value="random"<?php if($options['order'] == 'random') : ?> selected="selected"<?php endif; ?>><?php _e( 'Randomly', 'microkids-related-posts' ); ?></option>
        	    <select>
        	</p>
        </td>
        </tr>
        <tr valign="top">
        <th scope="row" style="width:300px;"><p><?php _e( 'Which post types can have related items?', 'microkids-related-posts' ); ?></p></th>
        <td>
            <?php $registered_post_types = explode(',', $options['post_types']) ?>
            <?php /* Builtin Post types */ ?>
            <p>
                <input <?php if (in_array('post', $registered_post_types)): ?>checked="checked"<?php endif ?> type="checkbox" name="MRP_custom_post_types[]" value="post" id="MRP_custom_type-post" />
                <label for="MRP_custom_type-post"><?php _e('Posts'); ?></label>
            </p>
            <p>
                <input <?php if (in_array('page', $registered_post_types)): ?>checked="checked"<?php endif ?> type="checkbox" name="MRP_custom_post_types[]" value="page" id="MRP_custom_type-page" />
                <label for="MRP_custom_type-page"><?php _e('Pages'); ?></label>
            </p>
    
            <?php foreach (get_post_types(array('_builtin' => false), 'object') as $post_type => $details): ?>
                <p>
                    <input <?php if (in_array($post_type, $registered_post_types)): ?>checked="checked"<?php endif ?> type="checkbox" name="MRP_custom_post_types[]" value="<?php echo $post_type ?>" id="MRP_custom_type-<?php echo $post_type ?>" />
                    <label for="MRP_custom_type-<?php echo $post_type ?>"><?php echo $details->label ?></label>
                </p>
            <?php endforeach ?>
        </td>
        </tr>
        <tr valign="top">
        <th scope="row" style="width:300px;"><p><?php _e( 'Would you like to display different post types as seperate lists or combine them into one related posts list?', 'microkids-related-posts' ); ?></p></th>
        <td>
            <p><input name="MRP_combine" type="radio" id="MRP_combine_false" value="0"<?php if( !$options['combine_post_types'] ) : ?> checked="checked"<?php endif; ?> /> <label for="MRP_combine_false"><?php _e( 'Display as seperate lists', 'microkids-related-posts' ); ?></label></p>
        	<p><input name="MRP_combine" type="radio" id="MRP_combine_true" value="1"<?php if( $options['combine_post_types'] ) : ?>checked="checked"<?php endif; ?> /> <label for="MRP_combine_true"><?php _e( 'Combine into one list', 'microkids-related-posts' ); ?></label></p>
        </td>
        </tr>
        </table>
        <p class="submit">
            <input name="MRP_options_submit" value="<?php _e( 'Save Changes', 'microkids-related-posts' ); ?>" type="submit" />
        </p>
        <p><small><?php printf( __('Note: If you need even more customization options, there are several <a%s>API functions</a> available.', 'microkids-related-posts', 'microkids-related-posts' ), ' href="http://www.microkid.net/wordpress/related-posts/#API" target="_blank"'); ?></small></p>
        
    </form>
    <?php if( ! isset( $options['hide_donate'] ) && ! isset( $_GET['hide_donate'] ) ) : ?>
    <div id="donate" class="extra" style="-moz-border-radius: 4px;-webkit-border-radius: 4px;border-radius: 4px;margin: 0 0 2em 0; background: #eee; font-size: 11px;padding: 10px; float: left">
        <h3 style="margin: 0 0 1em 0"><?php _e( 'Donate!', 'microkids-related-posts' ); ?></h3>
		<p><?php _e( 'Has this plugin saved your day? Are you using it for a commercial project? Cool! But please consider a donation for my work:', 'microkids-related-posts' ); ?></p>
		<form action="http://www.paypal.com/cgi-bin/webscr" method="post"><input id="cmd" name="cmd" value="_donations" type="hidden" style="float: left; margin: 0 0 1em 0;">
		    <div style="width: 70%;float: left;">
    		    <p class="donate_amount">
        		<select name="amount" id="amount" style="float: left;">
        			<option value="10">€ 10</option>
        			<option value="25">€ 25</option>
        			<option value="50">€ 50</option>
        			<option value="100">€ 100</option>
        			<option value="250">€ 250</option>
        		</select>
        		<input src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" name="submit" alt="" border="0" type="image" style="float: left; margin: 0 0 0 4px">
        		<img alt="" src="https://www.paypal.com/en_US/i/scr/pixel.gif" border="0" width="1" height="1">
        		</p>
        		<input name="notify_url" value="http://www.microkid.net/wp-content/plugins/donate-plus/paypal.php" type="hidden">
        		<input name="item_name" value="Has my work helped you out? Please consider a donation!" type="hidden">
        		<input name="business" value="bastiaanvandreunen@gmail.com" type="hidden">
        		<input name="lc" value="US" type="hidden">
        		<input name="no_note" value="1" type="hidden">
        		<input name="no_shipping" value="1" type="hidden">
        		<input name="rm" value="1" type="hidden">
        		<input name="return" value="http://www.microkid.net/thank-you/" type="hidden">
        		<input name="currency_code" value="EUR" type="hidden">
        		<input name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHosted" type="hidden">
        	</div>
    		<div style="float: right; text-align: right; font-size: 11px;width: 25%; padding: 20px 0 0 0"><a href="options-general.php?page=microkids-related-posts/microkids-related-posts.php&amp;hide_donate=1"><?php _e('Hide this message', 'microkids-related-posts' ); ?></a></div>
		</form>
	</div>
	<?php endif; ?>
</div>
<?php
	}
?>