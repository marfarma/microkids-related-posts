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
	
	$scope = (int) $_GET['mrp_scope'];
	
	$regexp = "[[:<:]]" . $s;
	
	$where = "";
	switch( $scope ) {
	
		case 1 :
			$where = "post_title REGEXP '$regexp'";
			break;
			
		case 2 :
			$where = "post_content REGEXP '$regexp'";
			break;
			
		default :
			$where = "( post_title REGEXP '$regexp' OR post_content REGEXP '$regexp' )";
			break;
	
	}
	
	$query = "SELECT ID, post_title, post_type, post_status FROM $wpdb->posts WHERE $where AND ( post_type = 'post' OR post_type = 'page' ) ";
	if( $_GET['mrp_id'] ) {
		$this_id = (int) $_GET['mrp_id'];
		$query .= " AND ID != $this_id ";
	}
	$query .= "ORDER BY post_date DESC LIMIT 50";
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
			echo $result->post_title;
			if( $result->post_status != 'publish') {
				echo ' ('.$result->post_status.')';
			}
			echo '</a> <a href="'.get_permalink( $result->ID ).'" title="View this post" class="MRP_view_post" target="_blank">&rsaquo;</a></li>';
			$n++;
		}
		echo "</ul>";

	}
}

?>
