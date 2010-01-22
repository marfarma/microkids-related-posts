// This uses the JQuery library that comes with Wordpress

jQuery(document).ready(function($){

	$("#MRP_search").bind( 'keydown', function(e){
		if( e.keyCode == 13 ){
			return false;
		}
	});

	var timer = 0;	
	$("#MRP_search").bind( 'keyup', function(e){
		if( ( e.keyCode > 47 && e.keyCode < 91 ) || e.keyCode == 8 || e.keyCode == 13 ){
			clearTimeout( timer );
			timer = setTimeout( function() {
						MRP_search();
					}, 200 );
		}
	});
	
	$("#MRP_scope input").each( function() {
		$(this).change(function() {
			MRP_search();
		});
	});		

	function MRP_search() {
		if( $("#MRP_search").val() != '' ) {
			var searchResults = "../wp-content/plugins/microkids-related-posts/mrp-search.php?mrp_s=" + escape( $("#MRP_search").val() );
			searchResults += "&mrp_scope=" + escape( $("input[name='MRP_scope']:checked").val() );
			if( $("#post_ID").val() ) {
				searchResults += "&mrp_id=" + escape( $("#post_ID").val() ); 
			}
			$("#MRP_results").load( searchResults, '', 
				function() { $("#MRP_results li .MRP_result").each(function(i) {
						$(this).click(function() {
							var postID = this.id.substring(7);
							var resultID = "related-post-" + postID;
							if( $("#"+resultID).text() == '' ) {
								$("#related-posts-replacement").hide();
								var newLI = document.createElement("li");
								$(newLI).attr('id', resultID);
								$(newLI).text($(this).text());
								$("#MRP_relatedposts_list").append( '<li id="'+resultID+'"><span>'+$(this).text()+'</span><span><a class="MRP_deletebtn" onclick="MRP_remove_relationship(\''+resultID+'\')">X</a></span><input type="hidden" name="MRP_related_posts[]" value="'+postID+'" /></li>' );
							}
							else {
								$("#"+resultID ).focus();
								$("#"+resultID ).css("color", "red");
								setTimeout('document.getElementById("'+resultID+'").style.color = "#000000";', 1350);
							}
						});	  					
					});
				}
			);
		}
		else {
			$("#MRP_results").html("");
		}
	}
});

function MRP_remove_relationship( postID ) {
	jQuery(document).ready(function($){
		$("#"+postID).remove();
		if( $("#MRP_relatedposts_list li").length < 2 ){
			$("#related-posts-replacement").show();
		}
	});
}