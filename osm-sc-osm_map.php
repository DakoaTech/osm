<?php
/*  (c) Copyright 2014  Michael Kang (wp-osm-plugin.HanBlog.Net)

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
    // let's get the shortcode arguments
  	extract(shortcode_atts(array(
    // size of the map
    'width'     => '450', 'height' => '300', 
    // address of the center in the map
    'lat'       => '', 'long'  => '',
    'lon'       => '',    
    // the zoomlevel of the map 
    'zoom'      => '7',     
    // Mapnik, CycleMap, ...           
    'type'      => 'AllOsm',
    // track info
    'gpx_file'  => 'NoFile',           // 'absolut address'          
    'gpx_file_proxy'  => 'NoFile',     // 'absolut address'          
    'gpx_colour'=> 'NoColour',
    'gpx_file_list'   => 'NoFileList',
    'gpx_colour_list' => 'NoColourList',
    'kml_file'  => 'NoFile',           // 'absolut address'          
    'kml_colour'=> 'NoColour',
    // are there markers in the map wished loaded from a file
    'marker_file'     => 'NoFile', // 'absolut address'
    'marker_file_proxy' => 'NoFile', // 'absolut address'
    'marker_file_list' => 'NoFileList', // 'absolut address for a list of marker files''
    // are there markers in the map wished loaded from post tags
    'marker_all_posts'=> 'n',      // 'y' or 'Y'
    'marker_name'     => 'NoName',
    'marker_height'   => '0',
    'marker_width'    => '0',
    'marker_focus'    => '0',
    'ov_map'          => '-1',         // zoomlevel of overviewmap
    'import'          => 'No',
    'import_osm_cat_incl_name'  => 'Osm_All',
    'import_osm_cat_excl_name'  => 'Osm_None',
    'import_osm_line_color' => 'none', 
    'import_osm_line_width' => '4',
    'import_osm_line_opacity' => '0.9',
    'post_type' => 'post',
    'custom_taxonomy' => 'none',
    'import_osm_custom_tax_incl_name'  => 'Osm_All',
    'marker'          => 'No',
    'marker_routing'  => 'No',
    'msg_box'         => 'No',
    'custom_field'    => 'No',
    'control'         => 'No',
    'extmap_type'     => 'No',
    'extmap_name'     => 'No',
    'extmap_address'  => 'No',
    'extmap_init'     => 'No',
    'map_border'      => 'none',
    'z_index'         => 'none',
    'm_txt_01'        => 'none',
    'm_txt_02'        => 'none',
    'm_txt_03'        => 'none',
    'm_txt_04'        => 'none',
    'theme'           => 'ol',
    'disc_center_list'          => '',          // in decimal degrees
    'disc_radius_list'          => '',          // in meters
    'disc_center_opacity_list'  => '0.5',       // float 0->1
    'disc_center_color_list'    => 'red',       // html name or #rvb or #rrvvbb
    'disc_border_width_list'    => '3',         // integer
    'disc_border_color_list'    => 'blue',      // html name or #rvb or #rrvvbb
    'disc_border_opacity_list'  => '0.5',      // float 0->1
    'disc_fill_color_list'      => 'lightblue',// html name or #rvb or #rrvvbb
    'disc_fill_opacity_list'    => '0.5'       // float 0->1

	  ), $atts));
   
    if (($zoom < ZOOM_LEVEL_MIN || $zoom > ZOOM_LEVEL_MAX) && ($zoom != 'auto')){
      $this->traceText(DEBUG_ERROR, "e_zoomlevel_range");
      $this->traceText(DEBUG_INFO, "Error: (Zoomlevel: ".$zoom.")!");
      $zoom = 0;   
    }
    if ($width < 1 || $height < 1){
      Osm::traceText(DEBUG_ERROR, "e_map_size");
      Osm::traceText(DEBUG_INFO, "Error: ($width: ".$width." $height: ".$height.")!");
      $width = 450; $height = 300;
    }

    if ($marker_name == 'NoName'){
      $marker_name  = POST_MARKER_PNG;
    }

    // All is replaced by AllOsm
    if ($type == 'All'){
      $type  = 'AllOsm';
    }

    // replace lon with long
    if ($lon != ''){
      $long  = $lon;
    }


    if (Osm_icon::isOsmIcon($marker_name) == 1){
       $Icon = Osm_icon::getIconsize($marker_name);
       $Icon["name"]  = $marker_name;
    }
    else  {
      $Icon["height"] = $marker_height;
      $Icon["width"]  = $marker_width; 
      $Icon["name"]  = $marker_name;
      if ($marker_focus == 0){ // center is default
        $Icon["offset_height"] = round(-$marker_height/2);
        $Icon["offset_width"] = round(-$marker_width/2);
      }
      else if ($marker_focus == 1){ // left bottom
        $Icon["offset_height"] = -$marker_height;
        $Icon["offset_width"]  = 0;
      }
      else if ($marker_focus == 2){ // left top
        $Icon["offset_height"] = 0;
        $Icon["offset_width"]  = 0;
      }
      else if ($marker_focus == 3){ // right top
        $Icon["offset_height"] = 0;
        $Icon["offset_width"]  = -$marker_width;
      }
      else if ($marker_focus == 4){ // right bottom
        $Icon["offset_height"] = -$marker_height;
        $Icon["offset_width"]  = -$marker_width;
      }
      else if ($marker_focus == 5){ // center bottom
        $Icon["offset_height"] = -$marker_height;
        $Icon["offset_width"] = round(-$marker_width/2);
      }
      if ($Icon["height"] == 0 || $Icon["width"] == 0){
        Osm::traceText(DEBUG_ERROR, "e_marker_size"); //<= ToDo
        $Icon["height"] = 24;
        $Icon["width"]  = 24;
      }
    }

    $arry_import = explode(',', $import);
    $import_type = strtolower($arry_import[0]);
    if(count($arry_import) > 1)
      $import_UserName = $arry_import[1];
    else{
      $import_UserName = 'DummyName';
    }

    $array_control = explode( ',', $control);
   
    list($lat, $long) = Osm::getMapCenter($lat, $long, $import_type, $import_UserName);
    if ($lat != 'auto' && $long != 'auto'){
      list($lat, $long) = Osm::checkLatLongRange('MapCenter',$lat, $long);
    }
    $gpx_colour       = Osm::checkStyleColour($gpx_colour); 
    $kml_colour       = Osm::checkStyleColour($kml_colour);
    $type             = Osm_OpenLayers::checkMapType($type);
    $ov_map           = Osm_OpenLayers::checkOverviewMapZoomlevels($ov_map);
	  
    $array_control    = Osm_OpenLayers::checkControlType($array_control);

    // to manage several maps on the same page
    // create names with index
    $MapCounter += 1;
    $MapName = 'map_'.$MapCounter;
    $GpxName = 'GPX_'.$MapCounter;
    $KmlName = 'KML_'.$MapCounter;
	
    Osm::traceText(DEBUG_INFO, "MapCounter = ".$MapCounter);
      
    // if we came up to here, let's load the map
    $output = '';	
    $output .= '<link rel="stylesheet" type="text/css" href="'.OSM_PLUGIN_URL.'/css/osm_map.css" />';
    $output .= '<style type="text/css">';
    if ($z_index != 'none'){ // fix for NextGen-Gallery
      $output .= '.entry, .olMapViewport, img {z-index: '.$z_index.' !important;}';   
      $output .= '.olControlNoSelect {z-index: '.$z_index.'+1.'.' !important;}';    
      $output .= '.olControlAttribution {z-index: '.$z_index.'+1.'.' !important;}';
    }      
    $output .= '#'.$MapName.' {clear: both; padding: 0px; margin: 0px; border: 0px; width: 100%; height: 100%; margin-top:0px; margin-right:0px;margin-left:0px; margin-bottom:0px; left: 0px;}';
    $output .= '#'.$MapName.' img{clear: both; padding: 0px; margin: 0px; border: 0px; width: 100%; height: 100%; position: absolute; margin-top:0px; margin-right:0px;margin-left:0px; margin-bottom:0px;}';
    $output .= '</style>';

    $output .= '<div id="'.$MapName.'" class="OSM_Map" style="width:'.$width.'px; height:'.$height.'px; overflow:hidden;padding:0px;border:'.$map_border.';">';

    
	if (Osm_LoadLibraryMode == SERVER_EMBEDDED){
          if(!defined('OL_LIBS_LOADED')) {
            $output .= '<script type="text/javascript" src="'.Osm_OL_LibraryLocation.'"></script>';
            define ('OL_LIBS_LOADED', 1);
        }
  
      if ($type == 'Mapnik' || $type == 'mapnik_ssl' || $type == 'Osmarender' || $type == 'basemap_at' || $type == 'stamen_watercolor' || $type == 'stamen_toner' || $type == 'CycleMap' || $type == 'AllOsm' || $type == 'Ext'){
        if(!defined('OSM_LIBS_LOADED')) {
          $output .= '<script type="text/javascript" src="'.Osm_OSM_LibraryLocation.'"></script>';
          define ('OSM_LIBS_LOADED', 1);
        }
      }
      elseif ($type == 'OpenSeaMap'){
        if(!defined('OSM_LIBS_LOADED')) {
          $output .= '<script type="text/javascript" src="'.Osm_OSM_LibraryLocation.'"></script>';
          $output .= '<script type="text/javascript" src="'.Osm_harbours_LibraryLocation.'"></script>';
          $output .= '<script type="text/javascript" src="'.Osm_map_utils_LibraryLocation.'"></script>';
          $output .= '<script type="text/javascript" src="'.Osm_utilities_LibraryLocation.'"></script>';
          define ('OSM_LIBS_LOADED', 1);
        }
      }
      elseif ($type == 'OpenWeatherMap'){
      	if(!defined('OSM_LIBS_LOADED'))  {
      	  $output .= '<script type="text/javascript" src="'.Osm_OSM_LibraryLocation.'"></script>';
      	  $output .= '<script type="text/javascript" src="'.Osm_openweather_LibraryLocation.'"></script>';
          define ('OSM_LIBS_LOADED', 1);
      	}
      }
      if ($type == 'GooglePhysical' || $type == 'GoogleStreet' || $type == 'GoogleHybrid' || $type == 'GoogleSatellite' || $type == 'AllGoogle' || $a_type == 'Ext'){
	    if(!defined('GOOGLE_LIBS_LOADED')) {
          $output .= '<script type="text/javascript" src="'.Osm_GOOGLE_LibraryLocation.'"></script>';
          define ('GOOGLE_LIBS_LOADED', 1);
        }
      }
      $output .= '<script type="text/javascript" src="'.OSM_PLUGIN_JS_URL.'osm-plugin-lib.js"></script>';
    }
      elseif (Osm_LoadLibraryMode == SERVER_WP_ENQUEUE){
      // registered and loaded by WordPress
      }
      else{
        $this->traceText(DEBUG_ERROR, "e_library_config");
      }
      $output .= '<script type="text/javascript">';
      $output .= '/* <![CDATA[ */';
      $output .= '(function($) {';

      if ($type == 'GooglePhysical' || $type == 'GoogleStreet' || $type == 'GoogleHybrid' || $type == 'GoogleSatellite' || $type == 'AllGoogle'){
        $output .= Osm_OpenLayers::addGoogleTileLayer($MapName, $type);
      }
      else{
        $output .= Osm_OpenLayers::addTileLayer($MapName, $type, $ov_map, $array_control, $extmap_type, $extmap_name, $extmap_address, $extmap_init, $theme);
      }

    // set center and zoom of the map
    $output .= Osm_OpenLayers::setMapCenterAndZoom($MapName, $lat, $long, $zoom);

    // add a clickhandler if needed
    $msg_box = strtolower($msg_box);
    if ( $msg_box == 'sc_gen' || $msg_box == 'lat_long' || $msg_box == 'metabox_sc_gen'){
      $output .= Osm_OpenLayers::AddClickHandler($MapName, $msg_box);
    }

    // Add the Layer with GPX Track
    if ($gpx_file_proxy != 'NoFile'){ 
      $GpxName = basename($gpx_file_proxy, ".gpx");
      $output .= Osm_OpenLayers::addVectorLayer($MapName, OSM_PLUGIN_URL."osm-proxy.php?url=".$gpx_file_proxy, $gpx_colour,'GPX');
    }

    if ($gpx_file != 'NoFile'){ 
      $GpxName = basename($gpx_file, ".gpx");
      $output .= Osm_OpenLayers::addVectorLayer($MapName, $gpx_file,$gpx_colour,'GPX');
    }

    if ($gpx_file_list != 'NoFileList'){
      $GpxFileListArray   = explode( ',', $gpx_file_list ); 
      $GpxColourListArray = explode( ',', $gpx_colour_list);
      $this->traceText(DEBUG_INFO, "(NumOfGpxFiles: ".sizeof($GpxFileListArray)." NumOfGpxColours: ".sizeof($GpxColourListArray).")!");
      if (sizeof($GpxFileListArray) == sizeof($GpxColourListArray)){
        for($x=0;$x<sizeof($GpxFileListArray);$x++){
          $GpxName = basename($GpxFileListArray[$x], ".gpx");
          $output .= Osm_OpenLayers::addVectorLayer($MapName, $GpxFileListArray[$x],$GpxColourListArray[$x],'GPX');
        }
      }
      else {
        $this->traceText(DEBUG_ERROR, "e_gpx_list_error");
      }
    }
    
    // Add the Layer with KML Track
    if ($kml_file != 'NoFile'){ 
      $output .= Osm_OpenLayers::addVectorLayer($MapName, $kml_file,$kml_colour,'KML');
    }

    // Add the marker here which we get from the file
    if ($marker_file_proxy != 'NoFile'){
      $MarkerName = basename($marker_file_proxy, ".txt");
      $output .= Osm_OpenLayers::addTextLayer($MapName, $MarkerName, OSM_PLUGIN_URL."osm-proxy.php?url=".$marker_file_proxy);
    }  
    
    if ($marker_file != 'NoFile'){    
      $MarkerName = basename($marker_file, ".txt");
      $output .= Osm_OpenLayers::addTextLayer($MapName, $MarkerName, $marker_file);
    }  
    if ($marker_file_list != 'NoFileList'){
      $MarkerFileListArray = explode( ',', $marker_file_list );
      $this->traceText(DEBUG_INFO, "(NumOfMarkerFiles: ".sizeof($MarkerFileListArray)."!");
      for($x=0;$x<sizeof($MarkerFileListArray);$x++){
        $MarkerLstName = basename($MarkerFileListArray[$x], ".txt");
      	$output .= Osm_OpenLayers::addTextLayer($MapName, $MarkerLstName, $MarkerFileListArray[$x]);
      }
     }      	
      	
    $marker_all_posts = strtolower($marker_all_posts);
    if ($marker_all_posts == 'y'){
      //$this->traceText(DEBUG_ERROR, "e_use_marker_all_posts");
      $import_type  = 'osm';
    }

    if ($import_type  != 'no'){
  $output .= Osm::getImportLayer($import_type, $import_UserName, $Icon, $import_osm_cat_incl_name,  $import_osm_cat_excl_name, $import_osm_line_color, $import_osm_line_width, $import_osm_line_opacity, $post_type, $import_osm_custom_tax_incl_name, $custom_taxonomy, $MapName);
    }
    if ($disc_center_list != ''){
      $centerListArray        = explode( ',', $disc_center_list );
      $radiusListArray        = explode( ',', $disc_radius_list );
      $centerOpacityListArray = explode( ',', $disc_center_opacity_list);
      $centerColorListArray   = explode( ',', $disc_center_color_list );
      $borderWidthListArray   = explode( ',', $disc_border_width_list );
      $borderColorListArray   = explode( ',', $disc_border_color_list );
      $borderOpacityListArray = explode( ',', $disc_border_opacity_list);
      $fillColorListArray     = explode( ',', $disc_fill_color_list );
      $fillOpacityListArray   = explode( ',', $disc_fill_opacity_list);
      $this->traceText(DEBUG_INFO, "(NumOfdiscs: ".sizeof($centerListArray)." NumOfradius: ".sizeof($radiusListArray).")!");

      if (sizeof($centerListArray) == sizeof($radiusListArray) && !empty($centerListArray) && !empty($radiusListArray)   ) {
        $output .= Osm_OpenLayers::addDiscs($centerListArray,$radiusListArray,$centerOpacityListArray,$centerColorListArray, $borderWidthListArray,$borderColorListArray,$borderOpacityListArray,$fillColorListArray,$fillOpacityListArray,$MapName);
      } else {
        $this->traceText(DEBUG_ERROR, "Discs parameters error");
      }
    }
  
   // just add single marker 
   if ($marker  == 'OSM_geo'){ 
     global $post;
     $Data = get_post_meta($post->ID, 'OSM_geo_data', true); 
     $PostMarker = get_post_meta($post->ID, 'OSM_geo_icon', true);

     $Data = preg_replace('/\s*,\s*/', ',',$Data);
     // get pairs of coordination
     $GeoData_Array = explode( ' ', $Data );
     list($temp_lat, $temp_lon) = explode(',', $GeoData_Array[0]); 

     $DoPopUp = 'false';

     // set the center of the map to the first geotag
     $lat = $temp_lat;
     $long = $temp_lon;

     if (Osm_icon::isOsmIcon($PostMarker) == 1){
       $Icon = Osm_icon::getIconsize($PostMarker);
       $Icon["name"]  = $PostMarker;
     }
     else {
      $this->traceText(DEBUG_ERROR, "e_not_osm_icon");
     }

     list($temp_lat, $temp_lon) = Osm::checkLatLongRange('Marker',$temp_lat, $temp_lon); 
     $MarkerArray[] = array('lat'=> $temp_lat,'lon'=>$temp_lon,'text'=>$temp_popup,'popup_height'=>'150', 'popup_width'=>'150');
     $output .= Osm_OpenLayers::addMarkerListLayer($MapName, $Icon,$MarkerArray,$DoPopUp);
   }
   else if ($marker  != 'No'){  
     global $post;
     $DoPopUp = 'true';
     list($temp_lat, $temp_lon, $temp_popup_custom_field) = explode(',', $marker);
	   if ($temp_popup_custom_field == ''){
		   $temp_popup_custom_field = 'osm_dummy';
	   }

     $temp_popup_custom_field = trim($temp_popup_custom_field);
     $temp_popup = get_post_meta($post->ID, $temp_popup_custom_field, true); 
 
     if ($m_txt_01 != 'none'){
       $temp_popup .= '<br>'.$m_txt_01;
     }
     if ($m_txt_02 != 'none'){
       $temp_popup .= '<br>'.$m_txt_02;
     }
     if ($m_txt_03 != 'none'){
       $temp_popup .= '<br>'.$m_txt_03;
     }	   
     if ($m_txt_04 != 'none'){
       $temp_popup .= '<br>'.$m_txt_04;
     }

     $marker_routing = strtolower($marker_routing);
     if ($marker_routing != 'no') { 
       $temp_popup .= '<br><div class="route"><a href="';
       if ($marker_routing == 'ors' || $marker_routing == 'openrouteservice') {  
         $temp_popup .= 'http://openrouteservice.org/index.php?end=' . $temp_lon . ',' . $temp_lat . '&zoom=' . $zoom . '&pref=Fastest&lang=' . substr(get_locale(),0,2) . '&noMotorways=false&noTollways=false';
       } 
       elseif ($marker_routing == 'cm' || $marker_routing == 'cloudmade') {  
         $temp_popup .= 'http://maps.cloudmade.com/?lat=' . $temp_lat . '&lng=' . $temp_lon . '&zoom=' . $zoom . '&directions=' . $temp_lat . ',' . $temp_lon . '&travel=car&styleId=1&active_page=0&opened_tab=1';
       }
       elseif ($marker_routing == 'yn' || $marker_routing == 'yournavigation') {  
         $temp_popup .= 'http://yournavigation.org/?tlat=' . $temp_lat . '&tlon=' . $temp_lon;
       }
       else {
         $temp_popup .= __("Missing routing service!", "OSM-plugin").$marker_routing;
         Osm::traceText(DEBUG_ERROR, "e_missing_rs_error");
       }
       $temp_popup .= '">' . __("Route from your location to this place", "OSM-plugin") . '</a></div>';
     }

     if (($temp_popup_custom_field == 'osm_dummy') && ($m_txt_01 == 'none') && ($marker_routing == 'no')){
       $DoPopUp = 'false';
     }

     list($temp_lat, $temp_lon) = Osm::checkLatLongRange('Marker',$temp_lat, $temp_lon); 
     $MarkerArray[] = array('lat'=> $temp_lat,'lon'=>$temp_lon,'text'=>$temp_popup,'popup_height'=>'150', 'popup_width'=>'150');
     $output .= Osm_OpenLayers::addMarkerListLayer($MapName, $Icon,$MarkerArray,$DoPopUp);
     //$output .= Osm_OpenLayers::addMarkerListLayerClust($MapName, $Icon,$MarkerArray,$DoPopUp);

    }

    // set center and zoom of the map
    $output .= Osm_OpenLayers::setMapCenterAndZoom($MapName, $lat, $long, $zoom);

    $output .= '})(jQuery)';
    $output .= '/* ]]> */';
    $output .= ' </script>';
    $output .= '</div>';
?>
