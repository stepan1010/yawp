/*
*	This file contains code responsible for displaying search results on Yawp Settings screen.
*	We use ajax to avoid page refresh and to load city information on the go.
*/

/* This function is responsible for connecting to a proxy page on our server and to
*	display search results on the Settings screen. It takes a callback function as a parameter.
*	Callback is used later to append a live listener to our search results.
*/
function yawp_find_city_script(callback){

	// Say "Loading" in the div where search results will be displayed to demonstrate that the search is going on
	var search_results_div = jQuery('.yawp_search_results');
	search_results_div.append("<br /><b>Loading</b>"); 

	// Take the name of the city we are looking for
	var yawp_city_to_find = jQuery("#yawp_city").val();

	// Init result variable
	var yawp_search_results = {};

	// Init data to send to the proxy page
	var data = {
		action: "yawp_find_city",
		yawp_city_to_find: yawp_city_to_find,
		dataType: "json"
	};

	// Sent data to the proxy page
	jQuery.post(ajaxurl, data, function(yawp_data){

		// On response, parse it
    	yawp_data = jQuery.parseJSON(yawp_data);
		
		// Empty search results div (which was saying "Loading")
		search_results_div.empty();
			
		// If we didn't get any response, say so.
		if (yawp_data.no_response) {
			search_results_div.append("<br /><b> Connection error. Please try again in a few minutes.</b>");
			return 0;
		} else {
			// If we got response, check if there are any cities first.
			if (yawp_data.no_city) {
				// If no city was found, say so
				search_results_div.append("<br /><b> No city found. Please check your spelling.</b>");
				return 0;
			} else {
				// If there was at least one city found, start parsing 
				for(i = 1; i <= yawp_data[0].count; i++) {

					// We create a radio button with all the data for each city that was found
					var radioBtn = jQuery('<br /> <input type="radio" name="yawp_list_of_possible_cities" value="' + i + '"> ' 
											+ yawp_data[i].name +', ' 
											+ yawp_data[i].country + 
											', Latitude: ' + yawp_data[i].latitude + 
											', Longitude: ' + yawp_data[i].longitude + 
											', Current temperature: ' + yawp_data[i].temperature + '&deg, ' + 
											'Current conditions: ' + yawp_data[i].conditions + '</input><br />');

					// Append newly created radio button to search results div
					radioBtn.appendTo('.yawp_search_results');

				};

				// Save our data to a variable for use outside of jQuery.post
				yawp_search_results = yawp_data;
			}
		}
		//Execute callback function
		callback(yawp_search_results);
	});
};  
	
// First, we need to wait for the whole page to load and then assing a function to our "Find" button
jQuery(document).ready(function() {
	
	// Assign search function to our button
	jQuery("#find_city").click( function() { 

		// Clean search results div from any previous results
		jQuery(".yawp_search_results").empty();

		// Call our find city script function
		yawp_find_city_script(function(yawp_city_list){

			// On response, check if there are any cities in the response
			if (yawp_city_list != 0){
				
				//If there are, add a live listener to our radio button to get id and name of a city when certain button is clicked
				jQuery("input:radio[name=yawp_list_of_possible_cities]").live('click', function() {
					
					// When a particular button is clicked, store id and name of selected city
					jQuery("#yawp_city_display_name").val(yawp_city_list[jQuery(this).val()].name);
					jQuery("input#yawp_city_id").val(yawp_city_list[jQuery(this).val()].id);
				});	
			};	
		}); 
	});		
});