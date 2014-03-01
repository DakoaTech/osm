<?php
/*
Plugin Name: OSM
Plugin URI: http://wp-osm-plugin.HanBlog.net
Description: Embeds maps in your blog and adds geo data to your posts.  Find samples and a forum on the <a href="http://wp-osm-plugin.HanBlog.net">OSM plugin page</a>.  Simply create the shortcode to add it in your post at [<a href="options-general.php?page=osm.php">Settings</a>]
Version: 2.6.1
Author: MiKa
Author URI: http://www.HanBlog.net
Minimum WordPress Version Required: 2.8
*/

/*  (c) Copyright 2014  Michael Kang

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
load_plugin_textdomain('OSM-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');

define ("PLUGIN_VER", "V2.6.1");

// modify anything about the marker for tagged posts here
// instead of the coding.
define ("POST_MARKER_PNG", "marker_posts.png");
define ('POST_MARKER_PNG_HEIGHT', 2);
define ('POST_MARKER_PNG_WIDTH', 2);

define ("GCSTATS_MARKER_PNG", "geocache.png");
define ('GCSTATS_MARKER_PNG_HEIGHT', 25);
define ('GCSTATS_MARKER_PNG_WIDTH', 25);

define ("INDIV_MARKER", "marker_blue.png");
define ('INDIV_MARKER_PNG_HEIGHT', 25);
define ('INDIV_MARKER_PNG_WIDTH', 25);

// these defines are given by OpenStreetMap.org
define ("URL_INDEX", "http://www.openstreetmap.org/index.html?");
define ("URL_LAT","&mlat=");
define ("URL_LON","&mlon=");
define ("URL_ZOOM_01","&zoom=[");
define ("URL_ZOOM_02","]");
define ('ZOOM_LEVEL_MAX',18); // standard is 17, only mapnik is 18
define ('ZOOM_LEVEL_MIN',1);

// other geo plugin defines
// google-maps-geocoder
define ("WPGMG_LAT", "lat");
define ("WPGMG_LON", "lng");

// some general defines
define ('LAT_MIN',-90);
define ('LAT_MAX',90);
define ('LON_MIN',-180);
define ('LON_MAX',180);

// tracelevels
define ('DEBUG_OFF', 0);
define ('DEBUG_ERROR', 1);
define ('DEBUG_WARNING', 2);
define ('DEBUG_INFO', 3);
define ('HTML_COMMENT', 10);

// Load OSM library mode
define ('SERVER_EMBEDDED', 1);
define ('SERVER_WP_ENQUEUE', 2);

define('OSM_PRIV_WP_CONTENT_URL', site_url() . '/wp-content' );
define('OSM_PRIV_WP_CONTENT_DIR', ABSPATH . 'wp-content' );
define('OSM_PRIV_WP_PLUGIN_URL', OSM_PRIV_WP_CONTENT_URL. '/plugins' );
define('OSM_PRIV_WP_PLUGIN_DIR', OSM_PRIV_WP_CONTENT_DIR . '/plugins' );
define('OSM_PLUGIN_URL', OSM_PRIV_WP_PLUGIN_URL."/osm/");
define('OSM_PLUGIN_ICONS_URL', OSM_PLUGIN_URL."icons/");
define('URL_POST_MARKER', OSM_PLUGIN_URL.POST_MARKER_PNG);
define('OSM_PLUGIN_THEMES_URL', OSM_PLUGIN_URL."themes/");
define('OSM_OPENLAYERS_THEMES_URL', WP_CONTENT_URL. '/uploads/osm/theme/' );
define('OSM_PLUGIN_JS_URL', OSM_PLUGIN_URL."js/");

global $wp_version;
if (version_compare($wp_version,"2.5.1","<")){
  exit('[OSM plugin - ERROR]: At least Wordpress Version 2.5.1 is needed for this plugin!');
}
	
// get the configuratin by
// default or costumer settings
if (@(!include('osm-config.php'))){
  include ('osm-config-sample.php');
}

// do not edit this
define ("Osm_TraceLevel", DEBUG_ERROR);
//define ("Osm_TraceLevel", DEBUG_INFO);

// If the function exists this file is called as upload_mimes.
// We don't do anything then.
if ( ! function_exists( 'osm_restrict_mime_types' ) ) {
  add_filter( 'upload_mimes', 'osm_restrict_mime_types' );
  /**
  * Retrun allowed mime types
  *
  * @see function get_allowed_mime_types in wp-includes/functions.php
  * @param array Array of mime types
  * @return array Array of mime types keyed by the file extension regex corresponding to those types.
  */
  function osm_restrict_mime_types( $mime_types ) {
    $mime_types['gpx'] = 'text/gpx';
    $mime_types['kml'] = 'text/kml';
    return $mime_types;
  }
}

// If the function exists this file is called as post-upload-ui.
// We don't do anything then.
if ( ! function_exists( 'osm_restrict_mime_types_hint' ) ) {
	// add to wp
	add_action( 'post-upload-ui', 'osm_restrict_mime_types_hint' );
	/**
	 * Get an Hint about the allowed mime types
	 *
	 * @return  void
	 */
	function osm_restrict_mime_types_hint() {
	  echo '<br />';
          _e('OSM plugin added: GPX / KML','OSM-plugin');
	}
}

//hook to create the meta box
add_action( 'add_meta_boxes', 'osm_map_create' );

function osm_map_create() {
  //create a custom meta box
  $screens = array( 'post', 'page' );
  foreach ($screens as $screen) {
    add_meta_box( 'osm-sc-meta', 'WP OSM Plugin shortcode generator', 'osm_map_create_function', $screen, 'normal', 'high' );
  }
}

function osm_map_create_function( $post ) {
?>
    <p>
    <b><?php _e('Generate','OSM-plugin') ?></b>:
    <select name="osm_mode">
        <option value="sc_gen">OSM shortcode</option>
        <option value="geotagging">geotag</option>
    </select><br>
    OSM shortcode options: <br>
    <b><?php _e('map type','OSM-plugin') ?></b>:
    <select name="osm_map_type">
        <option value="Mapnik">OpenStreetMap</option>
        <option value="CycleMap">CycleMap</option>
        <option value="OpenSeaMap">OpenSeaMap</option>
        <option value="OpenWeatherMap">OpenWeatherMap</option>
        <option value="basemap_at">BaseMap</option>
        <option value="stamen_watercolor">Stamen Watercolor</option>
        <option value="stamen_toner">Stamen Toner</option>
    </select>
    <br>
    <b><?php _e('OSM control theme','OSM-plugin') ?></b>: 
    <select name="osm_theme">
        <option value="none"><?php _e('none','OSM-plugin') ?></option>
        <option value="blue"><?php _e('blue','OSM-plugin') ?></option>
        <option value="dark"><?php _e('dark','OSM-plugin') ?></option>
        <option value="orange"><?php _e('orange','OSM-plugin') ?></option>
    </select>
    <b><?php _e('OSM marker','OSM-plugin') ?></b>:
    <select name="osm_marker">
        <option value="none">none</option>
        <option value="wpttemp-green.png"><?php _e('Waypoint Green','OSM-plugin') ?></option>
        <option value="wpttemp-red.png"><?php _e('Waypoint Red','OSM-plugin') ?></option>
        <option value="marker_blue.png"><?php _e('Marker Blue','OSM-plugin') ?></option>
        <option value="wpttemp-yellow.png"><?php _e('Marker Yellow','OSM-plugin') ?></option>
        <option value="car.png"><?php _e('Car','OSM-plugin') ?></option>
        <option value="bus.png"><?php _e('Bus','OSM-plugin') ?></option>
        <option value="bicycling.png"><?php _e('Bicycling','OSM-plugin') ?></option>
        <option value="airport.png"><?php _e('Airport','OSM-plugin') ?></option>
        <option value="motorbike.png"><?php _e('Motorbike','OSM-plugin') ?></option>
        <option value="hostel.png"><?php _e('Hostel','OSM-plugin') ?></option>
        <option value="guest_house.png"><?php _e('Guesthouse','OSM-plugin') ?></option>
        <option value="camping.png"><?php _e('Camping','OSM-plugin') ?></option>
        <option value="geocache.png"><?php _e('Geocache','OSM-plugin') ?></option>
        <option value="styria_linux.png"><?php _e('Styria Tux','OSM-plugin') ?></option>
    </select>
    </p>
<?php echo Osm::sc_showMap(array('msg_box'=>'metabox_sc_gen','lat'=>'50','long'=>'18.5','zoom'=>'3', 'type'=>'mapnik_ssl', 'width'=>'450','height'=>'300', 'map_border'=>'thin solid grey', 'theme'=>'dark', 'control'=>'mouseposition,scaleline')); ?>
  <br>
  <h3><span style="color:green"><?php _e('Copy the generated shortcode/customfield/argument: ','OSM-plugin') ?></span></h3>
  <div id="ShortCode_Div"><?php _e('If you click into the map this text is replaced','OSM-plugin') ?>
  </div><br>
  <?php
}

include('osm-oljs2.php');
include('osm-oljs3.php');
include('osm-icon.php');
    	
// let's be unique ... 
// with this namespace
class Osm
{ 

  function Osm() {
    $this->localizionName = 'Osm';
    //$this->TraceLevel = DEBUG_INFO;
	$this->ErrorMsg = new WP_Error();
	$this->initErrorMsg();
    
    // add the WP action
    add_action('wp_head', array(&$this, 'wp_head'));
    add_action('admin_menu', array(&$this, 'admin_menu'));
    add_action('wp_print_scripts',array(&$this, 'show_enqueue_script'));

    // add the WP shortcode
    add_shortcode('osm_map',array(&$this, 'sc_showMap'));
    add_shortcode('osm_ol3js',array(&$this, 'sc_OL3JS'));
    add_shortcode('osm_image',array(&$this, 'sc_showImage'));
    add_shortcode('osm_info',array(&$this, 'sc_info'));
  }

  function initErrorMsg()
  {
    include('osm-error-msg.php');	
  }

  function traceErrorMsg($e = '')
  {
   if ($this == null){
     return $e;
   }
   $EMsg = $this->ErrorMsg->get_error_message($e);
   if ($EMsg == null){
     return $e;
     //return__("Unknown errormessage",$this->localizionName); 
   }
   return $EMsg;
  }
  
  function traceText($a_Level, $a_String)
  {
    $TracePrefix = array(
    DEBUG_ERROR =>'[OSM-Plugin-Error]:',
    DEBUG_WARNING=>'[OSM-Plugin-Warning]:',
    DEBUG_INFO=>'[OSM-Plugin-Info]:');
      
    if ($a_Level == DEBUG_ERROR){     
      echo '<div class="osm_error_msg"><p><strong style="color:red">'.$TracePrefix[$a_Level].Osm::traceErrorMsg($a_String).'</strong></p></div>';
    }
    else if ($a_Level <= Osm_TraceLevel){
      echo $TracePrefix[$a_Level].$a_String.'<br>';
    }
    else if ($a_Level == HTML_COMMENT){
      echo "<!-- ".$a_String." --> \n";
    }
  }

	// add it to the Settings page
  function options_page_osm()
  {
    if(isset($_POST['Options'])){
      // 0 = no error; 
      // 1 = error occured
      $Option_Error = 0; 
			
      // get the zoomlevel for the external link
      // and inform the user if the level was out of range     
      // update_option('osm_custom_field',$_POST['osm_custom_field']);
     
      if ($_POST['osm_zoom_level'] >= ZOOM_LEVEL_MIN && $_POST['osm_zoom_level'] <= ZOOM_LEVEL_MAX){
        update_option('osm_zoom_level',$_POST['osm_zoom_level']);
      }
      else { 
        $Option_Error = 1;
        Osm::traceText(DEBUG_ERROR, "e_zoomlevel_range");
      }
      // Let the user know whether all was fine or not
      if ($Option_Error  == 0){ 
        Osm::traceText(DEBUG_INFO, "i_options_updated");
      }
      else{
        Osm::traceText(DEBUG_ERROR, "e_options_not_updated");
      }
    }
    else{
	  //add_option('osm_custom_field', 0);
	  add_option('osm_zoom_level', 0);
    }
	
    // name of the custom field to store Long and Lat
    // for the geodata of the post
    $osm_custom_field  = get_option('osm_custom_field','OSM_geo_data');                                                  

    // zoomlevel for the link the OSM page
    $osm_zoom_level    = get_option('osm_zoom_level','7');
			
    include('osm-options.php');	
  }
	
  // put meta tags into the head section
  function wp_head($not_used)
  { 
	global $wp_query;
	global $post;

    $lat = '';
    $lon = '';
    $CustomField =  get_option('osm_custom_field','OSM_geo_data');
    if (($CustomField != false) && (get_post_meta($post->ID, $CustomField, true))){
      $PostLatLon = get_post_meta($post->ID, $CustomField, true);
      if (!empty($PostLatLon)) {
        list($lat, $lon) = explode(',', $PostLatLon); 
      }
    }   

    if(is_single() && ($lat != '') && ($lon != '')){
      $title = convert_chars(strip_tags(get_bloginfo("name")))." - ".$wp_query->post->post_title;
      $this->traceText(HTML_COMMENT, 'OSM plugin '.PLUGIN_VER.': adding geo meta tags:');
    }
    else{
      $this->traceText(HTML_COMMENT, 'OSM plugin '.PLUGIN_VER.': did not add geo meta tags.');
    return;
    } 
    
    // let's store geo data with W3 standard
	echo "<meta name=\"ICBM\" content=\"{$lat}, {$lon}\" />\n";
	echo "<meta name=\"DC.title\" content=\"{$wp_query->post->post_title}\" />\n";
        echo "<meta name=\"geo.placename\" content=\"{$wp_query->post->post_title}\"/>\n"; 
	echo "<meta name=\"geo.position\"  content=\"{$lat};{$lon}\" />\n";
  }
    
 
  function createMarkerList($a_import, $a_import_UserName, $a_Customfield, $a_import_osm_cat_incl_name,  $a_import_osm_cat_excl_name, $a_post_type, $a_import_osm_custom_tax_incl_name, $a_custom_taxonomy)
  {
     $this->traceText(DEBUG_INFO, "createMarkerList(".$a_import.",".$a_import_UserName.",".$a_Customfield.")");
	 global $post;
     $post_org = $post;
      
     // make a dummymarker to you use icon.clone later
     if ($a_import == 'gcstats'){
       $this->traceText(DEBUG_INFO, "Requesting data from gcStats-plugin");
       include('osm-import.php');
     }
     else if ($a_import == 'ecf'){
       $this->traceText(DEBUG_INFO, "Requesting data from comments");
       include('osm-import.php');
     }
     else if ($a_import == 'osm' || $a_import == 'osm_l'){
       // let's see which posts are using our geo data ...
       $this->traceText(DEBUG_INFO, "check all posts for osm geo custom fields");
       $CustomFieldName = get_option('osm_custom_field','OSM_geo_data');        
       $recentPosts = new WP_Query();
       $recentPosts->query('meta_key='.$CustomFieldName.'&post_status=publish'.'&showposts=-1'.'&post_type='.$a_post_type.'');
//     $recentPosts->query('meta_key='.$CustomFieldName.'&post_status=publish'.'&post_type=page');
       while ($recentPosts->have_posts()) : $recentPosts->the_post();
         $Data = get_post_meta($post->ID, $CustomFieldName, true);
         // remove space before and after comma
         $Data = preg_replace('/\s*,\s*/', ',',$Data);
         // get pairs of coordination
         $GeoData_Array = explode( ' ', $Data );
  	 list($temp_lat, $temp_lon) = explode(',', $GeoData_Array[0]); 
         //echo $post->ID.'Lat: '.$temp_lat.'Long '.$temp_lon.'<br>';
         // check if a filter is set and geodata are set
         // if filter is set and set then pretend there are no geodata
       if (($a_import_osm_cat_incl_name  != 'Osm_All' || $a_import_osm_cat_excl_name  != 'Osm_None' || $a_import_osm_custom_tax_incl_name != 'Osm_All')&&($temp_lat != '' && $temp_lon != '')){
         $categories = wp_get_post_categories($post->ID);
         foreach( $categories as $catid ) {
	       $cat = get_category($catid);
           if (($a_import_osm_cat_incl_name  != 'Osm_All') && (strtolower($cat->name) != (strtolower($a_import_osm_cat_incl_name)))){
             $temp_lat = '';
             $temp_lon = '';
            }
            if (strtolower($cat->name) == (strtolower($a_import_osm_cat_excl_name))){
              $temp_lat = '';
              $temp_lon = '';
            }
         }    
         if ($a_import_osm_custom_tax_incl_name != 'Osm_All')
           $mycustomcategories = get_the_terms( $post->ID, $a_import_osm_custom_tax_incl_name);
         foreach( $mycustomcategories as $term ) {
           $taxonomies[0] = $term->term_taxonomy_id;
           // Get rid of the other data stored in the object
           unset($term);
         }
         foreach( $taxonomies as $taxid ) {
           $termsObjects = wp_get_object_terms($post->ID, $a_custom_taxonomy);
           foreach ($termsObjects as $termsObject) {
             $currentCustomCat[] = $termsObject->name;
           }
           if (($a_import_osm_custom_tax_incl_name  != 'Osm_All') &&  ! in_array($a_import_osm_custom_tax_incl_name, $currentCustomCat)) {
             $temp_lat = '';
             $temp_lon = '';
           }
           if (strtolower($currentCustomCat) == (strtolower($a_import_osm_cat_excl_name))){
             $temp_lat = '';
             $temp_lon = '';
           }
         }
       }
       if ($temp_lat != '' && $temp_lon != '') {
         // how many tags do we have in this post?
         $NumOfGeoTagsInPost = count($GeoData_Array);
         for ($TagNum = 0; $TagNum < $NumOfGeoTagsInPost; $TagNum++){
           list($tag_lat, $tag_lon) = explode(',', $GeoData_Array[$TagNum]); 
           list($tag_lat, $tag_lon) = $this->checkLatLongRange('$marker_all_posts',$tag_lat, $tag_lon);
           if ($a_import == 'osm_l' ){   
             $categories = wp_get_post_categories($post->ID);
	     // take the last one but ignore those without a specific category
             foreach( $categories as $catid ) {
	       $cat = get_category($catid);
               if ((strtolower($cat->name) == 'uncategorized') || (strtolower($cat->name) == 'allgemein')){
                 $Category_Txt = '';
               }
               else{
                 $Category_Txt = $cat->name.': ';
               }
             }
             $Marker_Txt = '<a href="'.get_permalink($post->ID).'">'.$Category_Txt.get_the_title($post->ID).'  </a>';
             $MarkerArray[] = array('lat'=> $tag_lat,'lon'=>$tag_lon,'popup_height'=>'100', 'popup_width'=>'150', 'marker'=>$Icon[name], 'text'=>$Marker_Txt);
           }	 
           else{ // plain osm without link to the post
             $Marker_Txt = ' ';
             $MarkerArray[] = array('lat'=> $tag_lat,'lon'=>$tag_lon,'popup_height'=>'100', 'popup_width'=>'150', 'marker'=>$Icon["name"], 'text'=>$Marker_Txt);
           }
         }
       }
       $this->traceText(DEBUG_INFO, "Found Marker ".count($MarkerArray));  
       endwhile;
     }
     else if ($a_import == 'wpgmg'){
       // let's see which posts are using our geo data ...
       $this->traceText(DEBUG_INFO, "check all posts for wpgmg geo custom fields");
       $recentPosts = new WP_Query();
       $recentPosts->query('meta_key='.WPGMG_LAT.'&meta_key='.WPGMG_LON.'&showposts=-1');
       while ($recentPosts->have_posts()) : $recentPosts->the_post();
         include('osm-import.php');
         if ($temp_lat != '' && $temp_lon != '') {
           list($temp_lat, $temp_lon) = $this->checkLatLongRange('$marker_all_posts',$temp_lat, $temp_lon);          
           $MarkerArray[] = array('lat'=> $temp_lat,'lon'=>$temp_lon,'marker'=>$Icon["name"],'popup_height'=>'100', 'popup_width'=>'200');
         }  
       endwhile;
     }
     $post = $post_org;
     return $MarkerArray;
  }

  // if you miss a colour, just add it
  function checkStyleColour($a_colour){
    if ($a_colour != 'red' && $a_colour != 'blue' && $a_colour != 'black' && $a_colour != 'green' && $a_colour != 'orange'){
      return "blue";
    }
    return $a_colour;
  }

  // get the layer for the markers
  function getImportLayer($a_type, $a_UserName, $Icon, $a_osm_cat_incl_name, $a_osm_cat_excl_name, $a_line_color, $a_line_width, $a_line_opacity, $a_post_type, $a_import_osm_custom_tax_incl_name, $a_custom_taxonomy, $a_MapName){

    if ($a_type  == 'osm_l'){
      $LayerName = 'TaggedPosts';
      if ($Icon["name"] != 'NoName'){ // <= ToDo
        $PopUp = 'true';     
      }
      else {
        $PopUp = 'false';
      }    
    }    
    
    // import data from tagged posts
    else if ($a_type  == 'osm'){
      $LayerName = 'TaggedPosts';
      $PopUp = 'false';
    }

    // import data from wpgmg
    else if ($a_type  == 'wpgmg'){
      $LayerName = 'TaggedPosts';
      $PopUp = 'false';
    }
    // import data from gcstats
    else if ($a_type == 'gcstats'){
      $LayerName     = 'GeoCaches';
      $PopUp = 'true';
      $Icon = Osm_icon::getIconsize(GCSTATS_MARKER_PNG);
      $Icon["name"] = GCSTATS_MARKER_PNG;
    }
    // import data from ecf
    else if ($a_type == 'ecf'){
      $LayerName = 'Comments';
      $PopUp = 'true';
      $Icon = Osm_icon::getIconsize(INDIV_MARKER);
      $Icon["name"] = INDIV_MARKER;
    }
    else{
      $this->traceText(DEBUG_ERROR, "e_import_unknwon");
    }
    $MarkerArray = $this->createMarkerList($a_type, $a_UserName,'Empty', $a_osm_cat_incl_name,  $a_osm_cat_excl_name, $a_post_type, $a_import_osm_custom_tax_incl_name, $a_custom_taxonomy);
    if ($a_line_color != 'none'){
      $line_color = Osm::checkStyleColour($a_line_color);
      $txt = Osm_OpenLayers::addLines($MarkerArray, $line_color, $a_line_width, $a_MapName);
    }
    $txt .= Osm_OpenLayers::addMarkerListLayer($a_MapName, $Icon, $MarkerArray, $PopUp);
    return $txt;
  }

 // check Lat and Long
  function getMapCenter($a_Lat, $a_Long, $a_import, $a_import_UserName){
    if ($a_import == 'wpgmg'){
      $a_Lat  = OSM_getCoordinateLat($a_import);
      $a_Long = OSM_getCoordinateLong($a_import);
    }
    else if ($a_import == 'gcstats'){
      if (function_exists('gcStats__getInterfaceVersion')) {
        $Val = gcStats__getMinMaxLat($a_import_UserName);
        $a_Lat = ($Val["min"] + $Val["max"]) / 2;
        $Val = gcStats__getMinMaxLon($a_import_UserName);
        $a_Long = ($Val["min"] + $Val["max"]) / 2;
      }
      else{
       $this->traceText(DEBUG_WARNING, "getMapCenter() could not connect to gcStats plugin");
       $a_Lat  = 0;$a_Long = 0;
      }
    }
    else if ($a_Lat == '' || $a_Long == ''){
      $a_Lat  = OSM_getCoordinateLat('osm');
      $a_Long = OSM_getCoordinateLong('osm');
    }
    return array($a_Lat,$a_Long);
  }
    
  // check Lat and Long
  function checkLatLongRange($a_CallingId, $a_Lat, $a_Long)
  {
    if ($a_Lat >= LAT_MIN && $a_Lat <= LAT_MAX && $a_Long >= LON_MIN && $a_Long <= LON_MAX &&
                    preg_match('!^[^0-9]+$!', $a_Lat) != 1 && preg_match('!^[^0-9]+$!', $a_Long) != 1){
      return array($a_Lat,$a_Long);              
    }
    else{
      $this->traceText(DEBUG_ERROR, "e_lat_lon_range");
      $this->traceText(DEBUG_INFO, "Error: ".$a_CallingId." Lat".$a_Lat." or Long".$a_Long);
      $a_Lat  = 0;$a_Long = 0;
    }
  }

  function getGPXName($filepath){
    $file = basename($filepath, ".gpx"); // $file is set to "index"
    return $file;
  }
  // shortcode for map with OpenLayers 3
  function sc_OL3JS($atts) {    
    static  $MapCounter = 0;
    include('osm-sc-ol3js.php');	
    return $output;
  }
  // shortcode for map with OpenLayers 2
  function sc_showMap($atts) {
    static  $MapCounter = 0;
    include('osm-sc-osm_map.php');
    return $output;
  }

  // shortcode for image OpenLayers 2
  function sc_showImage($atts) {
    include('osm-sc-osm_image.php');
    return $output;
  }	

  // shortcode for image OpenLayers 2
  function sc_info($atts) {
    include('osm-sc-info.php');
    return $output;
  }


 // add OSM-config page to Settings
  function admin_menu($not_used){
  // place the info in the plugin settings page
    add_options_page(__('OpenStreetMap Manager', 'Osm'), __('OSM', 'Osm'), 5, basename(__FILE__), array('Osm', 'options_page_osm'));
  }
  
  // ask WP to handle the loading of scripts
  // if it is not admin area
  function show_enqueue_script() {
    wp_enqueue_script(array ('jquery'));
	
	if (Osm_LoadLibraryMode == SERVER_EMBEDDED){
      // it is loaded when the map is displayed
	}
	elseif (Osm_LoadLibraryMode == SERVER_WP_ENQUEUE){
      //wp_enqueue_script('OlScript', 'http://www.openlayers.org/api/OpenLayers.js');
      //wp_enqueue_script('OsnScript', 'http://www.openstreetmap.org/openlayers/OpenStreetMap.js');
	  wp_enqueue_script('OlScript',Osm_OL_LibraryLocation);
          wp_enqueue_script('OsnScript',Osm_OSM_LibraryLocation);
          wp_enqueue_script('OsnScript',Osm_GOOGLE_LibraryLocation);
          wp_enqueue_script('OsnScript',OSM_PLUGIN_JS_URL.'osm-plugin-lib.js');
          define ('OSM_LIBS_LOADED', 1);
          define ('OL_LIBS_LOADED', 1);
          define ('GOOGLE_LIBS_LOADED', 1);
	}
	else{
	  // Errormsg is traced at another place
	}	
  }
}	// End class Osm

$pOsm = new Osm();

// This is meant to be the interface used
// in your WP-template

// returns Lat data of coordination
function OSM_getCoordinateLat($a_import)
{
  global $post;

  $a_import = strtolower($a_import);
  if ($a_import == 'osm' || $a_import == 'osm_l'){
	list($lat, $lon) = explode(',', get_post_meta($post->ID, get_option('osm_custom_field','OSM_geo_data'), true));
  }
  else if ($a_import == 'wpgmg'){
	$lat = get_post_meta($post->ID, WPGMG_LAT, true);
  }
  else {
    $this->traceText(DEBUG_ERROR, "e_php_getlat_missing_arg");
    $lat = 0;
  }
  if ($lat != '') {
    return trim($lat);
  } 
  return '';
}

// returns Lon data
function OSM_getCoordinateLong($a_import)
{
	global $post;
  
  $a_import = strtolower($a_import);
  if ($a_import == 'osm' || $a_import == 'osm_l'){
	  list($lat, $lon) = explode(',', get_post_meta($post->ID, get_option('osm_custom_field','OSM_geo_data'), true));
  }
  else if ($a_import == 'wpgmg'){
	  list($lon) = get_post_meta($post->ID,WPGMG_LON, true);
  }
  else {
    $this->traceText(DEBUG_ERROR, "e_php_getlon_missing_arg");
    $lon = 0;
  }
  if ($lon != '') {
	  return trim($lon);
  } 
  return '';
}

function OSM_getOpenStreetMapUrl() {
  $zoom_level = get_option('osm_zoom_level','7');  
  $lat = $lat == ''? OSM_getCoordinateLat('osm') : $lat;
  $lon = $lon == ''? OSM_getCoordinateLong('osm'): $lon;
  return URL_INDEX.URL_LAT.$lat.URL_LON.$lon.URL_ZOOM_01.$zoom_level.URL_ZOOM_02;
}

function OSM_echoOpenStreetMapUrl(){
  echo OSM_getOpenStreetMapUrl() ;
}
// functions to display a map in your theme 
// by using the custom fields
// default values should be set only at sc_showMap()
function OSM_displayOpenStreetMap($a_widht, $a_hight, $a_zoom, $a_type){

  $atts = array ('width'        => $a_widht,
                 'height'       => $a_hight,
                 'type'         => $a_type,
                 'zoom'         => $a_zoom,
	               'control'		  => 'off');

  if ((OSM_getCoordinateLong("osm"))&&(OSM_getCoordinateLat("osm"))) { 
    echo OSM::sc_showMap($atts);
  }
}

function OSM_displayOpenStreetMapExt($a_widht, $a_hight, $a_zoom, $a_type, $a_control, $a_marker_name, $a_marker_height, $a_marker_width, $a_marker_text, $a_ov_map, $a_marker_focus = 0, $a_routing = 'No', $a_theme = 'dark'){

  $atts = array ('width'          => $a_widht,
                 'height'         => $a_hight,
                 'type'           => $a_type,
                 'zoom'           => $a_zoom,
                 'ov_map'         => $a_ov_map,
                 'marker_name'    => $a_marker_name,
                 'marker_height'  => $a_marker_height,
                 'marker_width'   => $a_marker_width,
                 'marker'         => OSM_getCoordinateLat("osm") . ',' . OSM_getCoordinateLong("osm") . ',' . $a_marker_text,
	         	 'control'        => $a_control,
                 'marker_focus'   => $a_marker_focus,
                 'theme'          => $a_theme,
                 'marker_routing' => $a_routing);

  if ((OSM_getCoordinateLong("osm"))&&(OSM_getCoordinateLat("osm"))) { 
    echo OSM::sc_showMap($atts);
  }
}
?>
