<?php

	// This file is called using AJAX
	// when searching for related posts
	
if( isset( $_GET['mrp_s'] ) ) {
	
	require('../../../wp-config.php');
	
		// Let's keep this a tool for logged in users
	if( ! current_user_can("edit_posts") ) {
		die('Please log in');
	}

	global $wpdb;
	$s = $wpdb->escape( $_GET['mrp_s'] );
	
	$query = "SELECT ID, post_title, post_type FROM $wpdb->posts WHERE post_title LIKE '%$s%' AND ( post_type = 'post' OR post_type = 'page' ) AND post_status = 'publish'";
	if( $_GET['mrp_id'] ) {
		$this_id = (int) $_GET['mrp_id'];
		$query .= " AND ID != $this_id ";
	}
	$query .= "ORDER BY post_date DESC";
	$results = $wpdb->get_results( $query );
	
	if( $results ) {
	
		echo "<ul>";
		$n = 1;
		foreach( $results as $result ) {
			
			echo '<li';
			echo ( $n % 2 ) ? ' class="alt"' : '';
			echo '> <a href="javascript:void(0)" id="result-'.$result->ID.'" class="MRP_result">';
			if( $result->post_type == 'page') {
				echo "<strong>[Page]</strong> - ";
			}
			echo $result->post_title.'</a> <a href="'.get_permalink( $result->ID ).'" title="View this post" class="MRP_view_post">&rsaquo;</a></li>';
			$n++;
		}
		echo "</ul>";

	}
}

?>
