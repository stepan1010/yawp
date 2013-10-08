function yawp_find_city_script(callback){

	// Say "Loading" in the div where search results will be displayed to demonstrate that the search is going on

	jQuery('.yawp_search_results').append("<br /><b>Loading</b>"); 

	var yawp_city_to_find = jQuery("#yawp_city").val();
	var yawp_search_results = {};
	var data = {

		action: "yawp_find_city",
		yawp_city_to_find: yawp_city_to_find,
		dataType: "json"

	};
	jQuery.post(ajaxurl, data, function(yawp_data){

	    	yawp_data = jQuery.parseJSON(yawp_data);
			
			jQuery(".yawp_search_results").empty();
			
			if (yawp_data.no_response)
			{
				jQuery(".yawp_search_results").append("<br /><b> Connection error. Please try again in a few minutes.</b>");
				return 0;
			}
			else
			{

				if (yawp_data.no_city){ // We create a list of radio buttons in search results div in case there is more than one city with a given name
					jQuery(".yawp_search_results").append("<br /><b> No city found. Please check your spelling.</b>");
					return 0;
				}else{

					for(i=1; i<=yawp_data[0].count; i++)
					{

					var radioBtn = jQuery('<br /> <input type="radio" name="yawp_list_of_possible_cities" value="'+i+'"> ' 
											+yawp_data[i].name+', ' 
											+yawp_data[i].country+ 
											', Latitude: '+yawp_data[i].latitude+ 
											', Longitude: '+yawp_data[i].longitude+ 
											', Current temperature: '+yawp_data[i].temperature+'&deg, '+ 
											'Current conditions: '+yawp_data[i].conditions+'</input><br />');
					radioBtn.appendTo('.yawp_search_results');

					};
					yawp_search_results = yawp_data; // Save our data to a variable for use outside of jQuery.post
				}
			}
			callback(yawp_search_results);
		});

};  
	
	 
jQuery(document).ready(function() {
		
	jQuery("#find_city").click( function() { 

		jQuery(".yawp_search_results").empty(); // Clean the search results div from any previous results
		yawp_city_list = yawp_find_city_script( function(yawp_city_list){

			if (yawp_city_list != 0){

			jQuery("input:radio[name=yawp_list_of_possible_cities]").live('click', function() { //Add a live listener to get id and name of a city which user intersted in
			
				jQuery("#yawp_city_display_name").val(yawp_city_list[jQuery(this).val()].name);
				jQuery("input#yawp_city_id").val(yawp_city_list[jQuery(this).val()].id);
	
			});	
		};	



		}); 
		
	});

		
});