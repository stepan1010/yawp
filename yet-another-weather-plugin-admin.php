<?php

	/*
	* This is the settings page for Yawp plugin. A user my customize city and appearance settings here.
	* Once saved. The settings are being sent to this page via $_POST. An if statement below handles the data.
	*/

    if (isset($_POST['yawp_save_changes']) && $_POST['yawp_save_changes'] == 'Y') // Check if any new settings are recieved. If yes, update the database.
	{  

		/*
		* We store new settings in a variable for the later use in html form and write new settings to the database.
		*/

		$yawp_city_display_name = $_POST['yawp_city_display_name'];  
        update_option('yawp_city_display_name', $yawp_city_display_name);

        $yawp_widget_font_style = $_POST['yawp_widget_font_style'];
        update_option('yawp_widget_font_style', $yawp_widget_font_style);

        $yawp_widget_bg_style = $_POST['yawp_widget_bg_style'];
        update_option('yawp_widget_bg_style', $yawp_widget_bg_style);
     
        $yawp_city_id = $_POST['yawp_city_id'];  
    	if($yawp_city_id != get_option('yawp_city_id')) // Clear cached weather in case city id was changed
      	{
	        update_option('yawp_cached_weather', '');
	        update_option('yawp_city_id', $yawp_city_id);
    	}  	

        $yawp_cache_time = is_numeric(trim($_POST['yawp_cache_time'])) ? trim($_POST['yawp_cache_time']) : 30;

        /*
        * We don't need to modify the time of next connection to server unless the cache time was changed.
        * 
		*/
      	if($yawp_cache_time != get_option('yawp_cache_time')) 
      	{
	        $update_time = new DateTime(date("Y-m-d G:i:s"));
	        $update_time->modify('+' . $yawp_cache_time . ' minutes');

	        update_option('yawp_cache_time', $yawp_cache_time);
	        update_option('yawp_cache_time_formatted', $update_time->format("Y-m-d G:i:s"));
    	}
        /* 
        * Radio buttons and checkboxes and handled differently. $_POST returns "on" for the checkbox (if clicked)
        * and "true" for radio button (if selected). However, in the database we store "checked" for checkboxes and
        * the actual scale value for radiobuttons (C or F). This is done in order to fill html inputs directly,
        * i.e. for checkbox <input type="checkbox" ... echo $corresponding_php_variable. So, if a checkbox was checked,
        * php will output "checked" in the input's settings.
        */
 
       	$yawp_display_wind = $_POST['yawp_display_wind'] == "on" ? 'checked' : '' ;
	    $yawp_display_conditions = $_POST['yawp_display_conditions'] == "on" ? 'checked' : '' ;
	    $yawp_display_icon =  $_POST['yawp_display_icon'] == "on" ? 'checked' : '' ;
	    $yawp_display_temperature = $_POST['yawp_display_temperature'] == "on" ? 'checked' : '' ;
	    $yawp_scale_f = $_POST['yawp_scale'] == "F" ? 'checked' : '' ;
	    $yawp_scale_c = $_POST['yawp_scale'] == "C" ? 'checked' : '' ;

        // If scale changes clear the cache because we need to reload weather info for corrent temperature
        if($_POST['yawp_scale'] != get_option('yawp_scale')){
            update_option('yawp_cached_weather', '');
        }

	    /*
	    * After correct value has been assigned, all settings are written to the database
	    */

       	update_option('yawp_display_icon', $yawp_display_icon);
        update_option('yawp_display_conditions', $yawp_display_conditions);
        update_option('yawp_display_wind', $yawp_display_wind);
        update_option('yawp_display_temperature', $yawp_display_temperature);
		update_option('yawp_scale', $_POST['yawp_scale']);

?>
        <div class="updated"> <p><?php _e('Changes saved.' ); ?></p> </div>  
<?php

    } else {  

    	/*
		* If no new settings have been entered (if Save button was not pressed) then just load all the settings from database
		* and assign them to corresponing values.
		*/

		$yawp_city_display_name = get_option('yawp_city_display_name');  
        $yawp_city_latitude = get_option('yawp_city_latitude');  
        $yawp_city_longitude = get_option('yawp_city_longitude');
        $yawp_city_id = get_option('yawp_city_id');
        $yawp_display_wind = get_option('yawp_display_wind');
        $yawp_display_conditions = get_option('yawp_display_conditions');
       	$yawp_display_icon = get_option('yawp_display_icon');
        $yawp_display_temperature = get_option('yawp_display_temperature');
        $yawp_widget_bg_style = get_option('yawp_widget_bg_style');
        $yawp_widget_font_style = get_option('yawp_widget_font_style');

        $yawp_cache_time = get_option('yawp_cache_time') == '' ? 30 : get_option('yawp_cache_time');

        $yawp_scale_f = get_option('yawp_scale') == "F" ? 'checked' : '' ;
	    $yawp_scale_c = get_option('yawp_scale') == "C" ? 'checked' : '' ;
        
    }

?> 
<div class="wrap"> <!-- Here starts the front side of the settings page --> 
<?php    echo "<h2>" . __( 'Yawp Settings' ) . "</h2>"; ?>

<hr />
<?php    echo "<h3>" . __( 'Current City' ) . "</h3>"; 

echo '<p>' . __("Currently displaying: ") . $yawp_city_display_name . '</p>';
echo '<p>' . __("Current city openweathermap id: ") . $yawp_city_id . '</p><br /><hr />';

?>	

<?php    echo "<h3>" . __( 'New City Settings' ) . "</h3>"; ?>
      
	<p><?php _e("Enter a city to look up: "); ?><input type="text" id="yawp_city" name="yawp_city" value="" size="40"><?php _e(" Example: San Diego" ); ?></p>
	<button type="button" id="find_city">Find City</button>
	<br />
	<div class="yawp_search_results">
	<!-- List of cities that match entered name will be displayed in this div -->
	</div>
	
	<br />
	<!-- Here starts the form with actual settings -->   
    <form name="yawp_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">  

        <input type="hidden" name="yawp_save_changes" value="Y">  

		<input type="hidden" id="yawp_city_id" name="yawp_city_id" value="<?php echo $yawp_city_id; ?>" >  
     
        <br />    
        <hr />
        
        <?php    echo "<h3>" . __( 'Update Settings' ) . "</h3>"; ?>
        <p><?php _e("Please specify update interval in minutes: "); ?><input type="text" name="yawp_cache_time" value="<?php echo $yawp_cache_time; ?>" size="5"><?php _e(' 30 minutes by default' ); ?></p>
		<br />    
		<hr />

		<?php    echo "<h3>" . __( 'Appearance Settings' ) . "</h3>"; ?>

		<p><?php _e("City name to display on the page: "); ?><input type="text" id="yawp_city_display_name" name="yawp_city_display_name" value="<?php echo $yawp_city_display_name; ?>" size="40"><?php _e(' Edit this field if you want to have a custom name of the city to be displayed on the page. For example, you can change "Los Angeles" to LA or add a state/province label like "Vancouver, BC".' ); ?></p>

        <p><?php _e("Scale: " ); ?><input type="radio" name="yawp_scale" value="C" <?php echo $yawp_scale_c; ?>><?php _e("&degC " ); ?>    
        <input type="radio" name="yawp_scale" value="F" <?php echo $yawp_scale_f; ?>><?php _e("&degF " ); ?></p>

		<p><?php _e("Display weather icon: "); ?><input type="checkbox" name="yawp_display_icon" id="yawp_display_icon" <?php echo $yawp_display_icon; ?>></p>
		<p><?php _e("Display weather conditions: "); ?><input type="checkbox" name="yawp_display_conditions" id="yawp_display_conditions" <?php echo $yawp_display_conditions; ?>></p>
		<p><?php _e("Display wind speed: "); ?><input type="checkbox" name="yawp_display_wind" id="yawp_display_wind" <?php echo $yawp_display_wind; ?>></p>
		<p><?php _e("Display temperature: "); ?><input type="checkbox" name="yawp_display_temperature" id="yawp_display_temperature" <?php echo $yawp_display_temperature; ?>></p>
        <p><?php _e("Widget's background style: "); ?></p><textarea cols="70" rows="5" name="yawp_widget_bg_style" id="yawp_widget_bg_style" ><?php echo $yawp_widget_bg_style; ?></textarea>
        <p><?php _e("Widget's font style: "); ?></p><textarea cols="70" rows="5" name="yawp_widget_font_style" id="yawp_widget_font_style" ><?php echo $yawp_widget_font_style; ?></textarea>
				
        <p class="submit">  
        <input type="submit" name="Submit" value="<?php _e('Save Options', 'yawp_trdom' ) ?>" />  
        </p>  
    </form>
<?php
    // Donate button
	$donate_form = '<br /><p>Please donate to this plugin if you like it.';
	$donate_form .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">';
	$donate_form .= '<input type="hidden" name="cmd" value="_s-xclick">';
	$donate_form .= '<input type="hidden" name="hosted_button_id" value="9YJ6YFV7EJ8PG">';
	$donate_form .= '<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">';
	$donate_form .= '<img alt="" border="0" src="https://www.paypalobjects.com/ru_RU/i/scr/pixel.gif" width="1" height="1"></form></p>';

	echo $donate_form;
?>
</div> 