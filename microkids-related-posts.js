// This uses the JQuery library that comes with Wordpress

var MRP_search_tool_visible = false;

jQuery(document).ready(function($){  
	$("#MRP_search").keyup(function(){
		if( $("#MRP_search").val() != '' ) {
			$("#MRP_results").load("/wp-content/plugins/microkids-related-posts/mrp-search.php?mrp_s=" + escape( $("#MRP_search").val() ), '', 
				function() { $("#MRP_results li .MRP_result").each(function(i) {
						$(this).click(function() {

							var postID = this.id.substring(7);
							var resultID = "related-post-" + postID;
							
							if( $("#"+resultID).text() == '' ) {

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
	});		
});

function MRP_remove_relationship( postID ) {
	jQuery(document).ready(function($){
		$("#"+postID).remove();
	});
} 
