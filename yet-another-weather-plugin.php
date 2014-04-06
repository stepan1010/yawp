<?php
/*
Plugin Name: Yawp
Plugin URI: http://stepasyuk.com/yawp/
Description: Allows to retrive weather for a given city from openweathermap.org
Version: 1.1.3
Author: Stepan Stepasyuk
Author URI: http://stepasyuk.com
License: GPLv2
*/

/*
* Since version 1.1.0 it is possible to edit plugin's css file from the Options page. The code below checks
* is current version of the plugin is less than 1.1 and upgrades the plugin if needed.
*/

add_action('plugins_loaded', 'yawp_check_version');
function yawp_check_version()
{
	if(!get_option('yawp_version') || get_option('yawp_version') < 11){
		
		update_option('yawp_version', 11);

		// Adding default styling options
		update_option('yawp_widget_bg_style', "background:#1A1A1A;\r\nborder: 1px solid #141414;\r\nborder-radius: 5px;\r\nbox-shadow: 0 0 4px #303030;\r\n");
		update_option('yawp_widget_font_style', "color:#E8E8E8;\r\nfont-family:Helvetica;\r\n");
	}
}

/*
* Code below is responsible for handling unistallation hook
*/

register_uninstall_hook(__FILE__, "yawp_uninstall");

function yawp_uninstall()
{
	delete_option('yawp_scale'); // Temperature scale (C or F)
	delete_option('yawp_display_wind'); // Whether or not wind speed is displayed
	delete_option('yawp_display_temperature'); // Whether or not temperature is displayed
	delete_option('yawp_display_icon'); // Whether or not weather icon (weather conditions as picture) is displayed
	delete_option('yawp_display_conditions'); // Whether or not weather conditions are displayed (as text)
	delete_option('yawp_city_id'); // id of current city (openweathermaps id)
	delete_option('yawp_city_display_name'); // City name to be displayed (user selected)
	delete_option('yawp_cache_time'); // Interval in minutes between connections to the server
	delete_option('yawp_cache_time_formatted'); // Time of next update 
	delete_option('yawp_cached_weather'); // Current weather cache
	delete_option('yawp_version'); // Plugin version
	delete_option('yawp_widget_bg_style'); // Current settings for widget's background style
	delete_option('yawp_widget_font_style'); // Current settings for widget's font style
}

/*
* Code below is responsible for creating the settings page
*/

add_action('admin_menu', 'yawp_admin_actions'); // Displays link to our settings page in the admin menu

function yawp_admin_actions()
{
    $page_hook_suffix = add_options_page("Yawp", "Yawp", 1, "Yawp", "yawp_admin");

    /*
      * Use the retrieved $page_hook_suffix to hook the function that links our script.
      * This hook invokes the function only on our plugin administration screen,
      * see: http://codex.wordpress.org/Administration_Menus#Page_Hook_Suffix
      */

    add_action('admin_print_scripts-' . $page_hook_suffix, 'yawp_admin_scripts');    
}

function yawp_admin() // Function that includes the actual settings page
{ 
	include('yet-another-weather-plugin-admin.php');
}

/*
* Code below is responsible for working with city searching script on the settings page
*/

add_action( 'admin_init', 'yawp_admin_init' ); // Register our script
function yawp_admin_init()
{      
    wp_register_script( 'yawp-find-city-script', plugins_url( 'js/yawp-find-city-script.min.js', __FILE__ ) );
} 

function yawp_admin_scripts() // Link our already registered script to a page
{ 
	wp_enqueue_script( 'yawp-find-city-script' );
}

add_action('wp_ajax_yawp_find_city', 'yawp_find_city'); // Backend function handler for city searching script
function yawp_find_city()
{

$url = 'http://api.openweathermap.org/data/2.5/find?q='.str_replace(' ','+',$_POST["yawp_city_to_find"]);
$response = file_get_contents($url);

$decoded_response = json_decode($response);

	if (!isset($decoded_response->message)) // Handling no response
	{
		$result = json_encode(array(
			'no_response' => 1
		));
		die();
	};
	
	if (isset($decoded_response->count) && $decoded_response->count > 0) // Handler in case there is a response and there is 1 or more cities
	{

		$result = array();
		array_push($result, array('count' => $decoded_response->count)); // Set the total number of found cities

		for ($i = 0; $i < $decoded_response->count; $i++)
		{

			// Extract the temperature separatly since this parameter depends on the selected scale. Calculations are made on the fly
			$temperature = get_option('yawp_scale') == "F" ? round((($decoded_response->list[$i]->main->temp) - 273.15) * 1.8 + 32) : round(($decoded_response->list[$i]->main->temp) - 273.15);

			array_push($result, array(
			'id' => $decoded_response->list[$i]->id,
			'name' => $decoded_response->list[$i]->name,
			'country' => $decoded_response->list[$i]->sys->country,
			'temperature' => $temperature,
			'latitude' => $decoded_response->list[$i]->coord->lat,
			'longitude' => $decoded_response->list[$i]->coord->lon,
			'conditions' => $decoded_response->list[$i]->weather[0]->main
			));
		}

		$result = json_encode($result);

	}
	else // If no cities are found return corresponding message
	{

		$result = json_encode(array(
			'no_city' => 1
		));
	};
	
	echo $result;
	die();

}

/*
* Code below is responsible for user-facing side output
*/

add_action('wp_enqueue_scripts', 'register_yawp_styles'); // Register style sheet
function register_yawp_styles() 
{
	wp_register_style('yawp', plugins_url( 'css/yawpstyle.css', __FILE__ ));
	wp_enqueue_style('yawp');
}

add_shortcode("yawpdisplay", "yawp_display"); // Register a shortcode for output
function yawp_display()
{
	
	if(!get_option('yawp_cache_time_formatted'))
	{
		$update_time = new DateTime(date("Y-m-d G:i:s"));
		$update_time->modify('+30 minutes');
		update_option('yawp_cache_time_formatted', $update_time->format("Y-m-d G:i:s"));

	}else{
		$update_time = get_option('yawp_cache_time_formatted');
	}

	$current_time = new DateTime(date("Y-m-d G:i:s"));
	

	if($current_time > $update_time || get_option('yawp_cached_weather') == '')
	{
		$weather_array = yawp_get_current_weather(); // Get current weather
	}else{
		$weather_array = unserialize(get_option('yawp_cached_weather'));
	}

	if($weather_array == 'No city selected.') // If no city was selected in the settings
	{
		$retval .= '<div id="yawp_weather_widget" class="widget">';
		$retval .= '<div id="yawp_city" class="yawp_weather">No city selected.</div>';
		$retval .= '</div><br />';

		return $retval;
		die();
	}

	$retval = '';
	$retval .= '<div id="yawp_weather_widget" class="widget" style="'.get_option('yawp_widget_bg_style').get_option('yawp_widget_font_style').'">';
	$retval .= '<div id="yawp_city_name_section" class="yawp_weather">'.$weather_array["name"].'</div>';

	(get_option('yawp_display_icon') == "checked") ? $retval .= '<div id="yawp_weather_icon_section" class="yawp_weather" '.$weather_array["weather_icon"].'></div>' : '';
	(get_option('yawp_display_temperature') == "checked") ? ($retval .= '<div id="yawp_temperature_section" class="yawp_weather">'.$weather_array["temperature"].'</div>') : '';
 	(get_option('yawp_display_conditions') == "checked") ? $retval .= '<div id="yawp_conditions_section" class="yawp_weather">'.$weather_array["conditions"].'</div>' :'' ;
	(get_option('yawp_display_wind') == "checked") ? $retval .= '<div id="yawp_wind_section" class="yawp_weather">'.$weather_array["wind"].'</div>': '';

	$retval .= '</div><br />';
	
	return $retval;

	die();
}

function yawp_get_current_weather() //Function is very similar to the one that handles city search. Search is based on city id
{

	if(get_option('yawp_city_id') != '')
	{
		$yawp_city_id = get_option('yawp_city_id');
	}
	else
	{
		return 'No city selected.';
		die();
	}

	$yawp_city_display_name = get_option('yawp_city_display_name');

	$url = 'http://api.openweathermap.org/data/2.5/weather?id='.$yawp_city_id;
	$response = file_get_contents($url);

	$decoded_response = json_decode($response);

	$weather_id = $decoded_response->weather[0]->id;
	$time_of_day = substr($decoded_response->weather[0]->icon, -1) == "n" ? "night" : "day" ;

	$image_url = plugins_url('yet-another-weather-plugin/'); // An appropriate icon is selected based on the received weather code
	if ($weather_id <=232) {
		$weather_icon = 'images/thunderstorm_'.$time_of_day.'.png';
	}elseif ($weather_id <=321) {
		$weather_icon = 'images/drizzle_'.$time_of_day.'.png';
	}elseif ($weather_id <=522) {
		$weather_icon = 'images/rain_'.$time_of_day.'.png';
	}elseif ($weather_id <=600) {
		$weather_icon = 'images/lightsnow_'.$time_of_day.'.png';
	}elseif ($weather_id <=601) {
		$weather_icon = 'images/snow_'.$time_of_day.'.png';
	}elseif ($weather_id <=611) {
		$weather_icon = 'images/heavysnow_'.$time_of_day.'.png';
	}elseif ($weather_id <=621) {
		$weather_icon = 'images/showersnow_'.$time_of_day.'.png';
	}elseif ($weather_id <=741) {
		$weather_icon = 'images/fog_'.$time_of_day.'.png';
	}elseif ($weather_id <=800) {
		$weather_icon = 'images/clear_'.$time_of_day.'.png';	
	}elseif ($weather_id <=801) {
		$weather_icon = 'images/fewclouds_'.$time_of_day.'.png';
	}elseif ($weather_id <=802) {
		$weather_icon = 'images/scatteredclouds_'.$time_of_day.'.png';
	}elseif ($weather_id <=803) {
		$weather_icon = 'images/brokenclouds_'.$time_of_day.'.png';
	}elseif ($weather_id <=804) {
		$weather_icon = 'images/overcastclouds_'.$time_of_day.'.png';
	}elseif ($weather_id <=906) {
		$weather_icon = 'images/hail_'.$time_of_day.'.png';
	}elseif ($weather_id <=905) {
		$weather_icon = 'images/windy_'.$time_of_day.'.png';
	}elseif ($weather_id <=904) {
		$weather_icon = 'images/heat_'.$time_of_day.'.png';
	};

	$weather_icon = 'style=background-image:url(\''.$image_url.$weather_icon.'\');height:120px;';
	$temperature = get_option('yawp_scale') == "F" ? round((($decoded_response->main->temp) - 273.15) * 1.8 + 32).' &deg'.get_option('yawp_scale') : round(($decoded_response->main->temp) - 273.15).' &deg'.get_option('yawp_scale');
	$conditions = $decoded_response->weather[0]->main;	
	$wind = 'Wind '.round($decoded_response->wind->speed).' m/s';

	
	
	$result = array(
		'name' => $yawp_city_display_name,
		'temperature' => $temperature,
		'conditions' => $conditions,
		'wind' => $wind,
		'weather_icon' => $weather_icon
		);

	update_option('yawp_cached_weather', serialize($result));

	return $result;

	die();

}

/*
* Widget area
*/

// Register our widget
add_action('widgets_init', 'yawp_register_widget');
function yawp_register_widget(){
     register_widget('Yawp_Widget');
};

class Yawp_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'yawp_widget',
			'Yawp Widget',
			array('description' => __('Yawp Widget to display weather information in the widget area. '),)
		);
	}

	/* 
	* The logic of the widget is very similar to yawp_display(). The only difference is that yawp_display returns the 
	* output while this functions echo's it. These functions are separated on purpose in order to avoid possible incompatibilities 
	* with some themes
	*/
	public function widget() 
	{
		if(get_option('yawp_cache_time_formatted') == '') // Create a new update time if there isn't any
		{
			$update_time = new DateTime(date("Y-m-d G:i:s"));
			$update_time->modify('+30 minutes');
			update_option('yawp_cache_time_formatted', $update_time->format("Y-m-d G:i:s"));
			update_option('yawp_cache_time', 30);		
		}else{
			$update_time = new DateTime(get_option('yawp_cache_time_formatted'));
		}

		$current_time = new DateTime(date("Y-m-d G:i:s"));

		if($current_time > $update_time || get_option('yawp_cached_weather') == '') // Connect to server in case the update time has expired
		{
			$weather_array = yawp_get_current_weather(); // Get current weather
			$update_time = new DateTime(date("Y-m-d G:i:s")); 
			$update_time->modify('+'.get_option('yawp_cache_time').' minutes');
			update_option('yawp_cache_time_formatted', $update_time->format("Y-m-d G:i:s")); // Set the new update time;
		}else{
			$weather_array = unserialize(get_option('yawp_cached_weather')); 
		}

		if($weather_array == 'No city selected.') // If no city was selected in the settings
		{
			$retval .= '<div id="yawp_weather_widget" class="widget">';
			$retval .= '<div id="yawp_city" class="yawp_weather">No city selected.</div>';
			$retval .= '</div><br />';

			echo $retval;
			return 0;
		}

		$retval = '';
		$retval .= '<div id="yawp_weather_widget" class="widget" style="'.get_option('yawp_widget_bg_style').get_option('yawp_widget_font_style').'">';
		$retval .= '<div id="yawp_city_name_section" class="yawp_weather">'.$weather_array["name"].'</div>';

		(get_option('yawp_display_icon') == "checked") ? $retval .= '<div id="yawp_weather_icon_section" class="yawp_weather" '.$weather_array["weather_icon"].'></div>' : '';
		(get_option('yawp_display_temperature') == "checked") ? ($retval .= '<div id="yawp_temperature_section" class="yawp_weather">'.$weather_array["temperature"].'</div>') : '';
	 	(get_option('yawp_display_conditions') == "checked") ? $retval .= '<div id="yawp_conditions_section" class="yawp_weather">'.$weather_array["conditions"].'</div>' :'' ;
		(get_option('yawp_display_wind') == "checked") ? $retval .= '<div id="yawp_wind_section" class="yawp_weather">'.$weather_array["wind"].'</div>': '';

		$retval .= '</div>';
		
		echo $retval;

	}

	/* Since widget settings form in the widget area is too small to create enough inputs of sufficient size we will gently
	* advise the user to proceed to the actual settings page
	*/ 
 	public function form() 
 	{
		echo '<p><strong>Please change settings under "Settings" -> "Yawp" </strong></p>';
	}

	public function update() 
	{
		// No code here since all settings are handled through settings page.
	}
}

?>