<?php

    extract(shortcode_atts(array(
    // size of the map
    'width'   => '450', 
    'height'  => '300',
    'lat'     => '48.213',
    'lon'     => '16.378',
    'zoom'    => '4',
    'kml_file'=> 'NoFile',
    'type'      => 'Osm',
    'jsname'  => 'dummy',
    'marker'  => 'No',
    'map_border'  => 'none',
    'marker_name' => 'NoName'
    ), $atts));
    $VectorLayer_Marker = 'NO';
    $VectorLayer_File = 'NO';
    $type = strtolower($type);

    $pos = strpos($width, "%");
    if ($pos == false) {
      if ($width < 1){
        Osm::traceText(DEBUG_ERROR, "e_map_size");
        Osm::traceText(DEBUG_INFO, "Error: ($width: ".$width.")!");
        $width = 450;
      }
      $width_str = $width."px"; // make it 30px
    } else {// it's 30%
      $width_perc = substr($width, 0, $pos ); // make it 30 
      if (($width_perc < 1) || ($width_perc >100)){
        Osm::traceText(DEBUG_ERROR, "e_map_size");
        Osm::traceText(DEBUG_INFO, "Error: ($width: ".$width.")!");
        $width = "100%";
      }
      $width_str = substr($width, 0, $pos+1 ); // make it 30% 
    }

    $pos = strpos($height, "%");
    if ($pos == false) {
      if ($height < 1){
        Osm::traceText(DEBUG_ERROR, "e_map_size");
        Osm::traceText(DEBUG_INFO, "Error: ($height: ".$height.")!");
        $height = 300;
      }
      $height_str = $height."px"; // make it 30px
    } else {// it's 30%
      $height_perc = substr($height, 0, $pos ); // make it 30 
      if (($height_perc < 1) || ($height_perc >100)){
        Osm::traceText(DEBUG_ERROR, "e_map_size");
        Osm::traceText(DEBUG_INFO, "Error: ($height: ".$height.")!");
        $height = "100%";
      }
      $height_str = substr($height, 0, $pos+1 ); // make it 30% 
    }

    $marker_name = Osm_icon::replaceOldIcon($marker_name);

    $MapCounter += 1;
    $MapName = 'map_ol3js_'.$MapCounter;
    $showMapInfoDiv = 0;
    $MapInfoDiv = $MapName.'_info';

    $output = '<div id="'.$MapName.'" class="OSM_Map" style="width:'.$width_str.'; height:'.$height_str.'; overflow:hidden;border:'.$map_border.';">';

    if(!defined('OL3_LIBS_LOADED')) {
      $output .= '<link rel="stylesheet" href="'.Osm_OL_3_CSS.'" type="text/css"> ';
      $output .= '<script src="'.Osm_OL_3_LibraryLocation.'" type="text/javascript"></script> ';
      define ('OL3_LIBS_LOADED', 1);
    }
 
    $output .= '<script type="text/javascript">'; 
    $output .= '/* <![CDATA[ */';
    $output .= '(function($) {';

    if ($jsname == "dummy"){
      $ov_map = "ov_map";
      $array_control = "array_control";
      $extmap_type = "extmap_type";
      $extmap_name = "extmap_name";
      $extmap_address = "extmap_address";
      $extmap_init = "extmap_init";
      $theme = "theme";
      $output .= Osm_OLJS3::addTileLayer($MapName, $type, $ov_map, $array_control, $extmap_type, $extmap_name, $extmap_address, $extmap_init, $theme);
    }
    else {
      $output .= file_get_contents($jsname);
    }
    if ($kml_file != "NoFile"){
      $VectorLayer_File = "kml";
      $showMapInfoDiv = 1;

      $output .= '
        var style = {
          "Point": [new ol.style.Style({
             image: new ol.style.Circle({
               fill: new ol.style.Fill({
                 color: "rgba(255,255,0,0.4)"
               }),
               radius: 5,
               stroke: new ol.style.Stroke({
                 color: "#ff0",
                 width: 1
               })
             })
           })],
           "LineString": [new ol.style.Style({
             stroke: new ol.style.Stroke({
               color: "#f00",
               width: 3
             })
           })],
           "MultiLineString": [new ol.style.Style({
             stroke: new ol.style.Stroke({
               color: "#0f0",
               width: 3
             })
           })]
         };
       ';
       $Colour = "green";
       $LayerName = "LayerName";

       $output .= Osm_OLJS3::addVectorLayer($LayerName, $kml_file, $Colour, "kml");
    }
    if ($marker  == 'OSM_geo_widget'){ 
      $VectorLayer_Marker = $marker;
      global $post;
      $CustomFieldName = get_option('osm_custom_field','OSM_geo_data');
      $Data = get_post_meta($post->ID, $CustomFieldName, true);  
      $PostMarker = get_post_meta($post->ID, 'OSM_geo_icon', true);
      if ($PostMarker == ""){
        $PostMarker = $marker_name;
      }

      $Data = preg_replace('/\s*,\s*/', ',',$Data);
      // get pairs of coordination
      $GeoData_Array = explode( ' ', $Data );
      list($temp_lat, $temp_lon) = explode(',', $GeoData_Array[0]); 
      $DoPopUp = 'false';

      $PostMarker = Osm_icon::replaceOldIcon($PostMarker);
      if (Osm_icon::isOsmIcon($PostMarker) == 1){
        $Icon = Osm_icon::getIconsize($PostMarker);
        $Icon["name"]  = $PostMarker;
      }
      else { // if no marker is set for the post
        $this->traceText(DEBUG_ERROR, "e_not_osm_icon");
        $this->traceText(DEBUG_ERROR, $PostMarker);
        $Icon = Osm_icon::getIconsize($PostMarker);
        $Icon["name"]  = $marker_name;
      }

      list($temp_lat, $temp_lon) = Osm::checkLatLongRange('Marker',$temp_lat, $temp_lon,'no');
      if (($temp_lat != 0) || ($temp_lon != 0)){
      // set the center of the map to the first geotag
        $lat = $temp_lat;
        $lon = $temp_lon;
        $MarkerArray[] = array('lat'=> $temp_lat,'lon'=>$temp_lon,'text'=>$temp_popup,'popup_height'=>'150', 'popup_width'=>'150');
        $output .= '
        var iconFeature = new ol.Feature({
          geometry: new ol.geom.Point(
            ol.proj.transform(['.$lon.', '.$lat.'], "EPSG:4326", "EPSG:3857")),
          name: "Mein Inhalt",
        });

        var iconStyle = new ol.style.Style({
          image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
            anchor: [0.5, 46],
            anchorXUnits: "fraction",
            anchorYUnits: "pixels",
            opacity: 0.9,
            src: "'.OSM_PLUGIN_ICONS_URL.$Icon["name"].'"
          }))
        });
        iconFeature.setStyle(iconStyle);

        var vectorMarkerSource = new ol.source.Vector({
          features: [iconFeature]
        });

        var vectorMarkerLayer = new ol.layer.Vector({
          source: vectorMarkerSource
        });


        ';
      }// templat lon != 0
    } //($marker  == 'OSM_geo_widget')
    if ($type == "openseamap"){
      $output .= '
      var '.$MapName.' = new ol.Map({
        layers: [raster, Layer2],
        target: "'.$MapName.'",
        view: new ol.View({
          center: ol.proj.transform(['.$lon.','.$lat.'], "EPSG:4326", "EPSG:3857"),
          zoom: '.$zoom.'
        })
      });';
    }
    else{
      $output .= '
      var '.$MapName.' = new ol.Map({
        layers: [raster],
        target: "'.$MapName.'",
        view: new ol.View({
          center: ol.proj.transform(['.$lon.','.$lat.'], "EPSG:4326", "EPSG:3857"),
          zoom: '.$zoom.'
        })
      });
      ';
    }
    if ($VectorLayer_Marker != "NO"){
      $output .= '
      '.$MapName.'.addLayer(vectorMarkerLayer);





var element = document.createElement("div");
element.className = "myclass";
element.innerHTML = iconFeature.get("name");

var popup = new ol.Overlay({
  element: element,
  positioning: "bottom-center",
  stopEvent: false
});
'.$MapName.'.addOverlay(popup);

// display popup on click
'.$MapName.'.on("click", function(evt) {
  var feature = '.$MapName.'.forEachFeatureAtPixel(evt.pixel,
      function(feature, layer) {
        return feature;
      });

  if (feature) {
    var geometry = feature.getGeometry();
    var coord = geometry.getCoordinates();
    popup.setPosition(coord);
 /**   $(element).popover({
      "placement": "top",
      "html": true,
      "content": "test";
    });
    $(element).popover("show");*/
  } 
  else {
   /** $(element).popover("destroy");*/
  }
});


// change mouse cursor when over marker
'.$MapName.'.on("pointermove", function(e) {
  if (e.dragging) {
    $(element).popover("destroy");
    return;
  }
  var pixel = '.$MapName.'.getEventPixel(e.originalEvent);
  var hit = '.$MapName.'.hasFeatureAtPixel(pixel);
  '.$MapName.'.getTarget().style.cursor = hit ? "pointer" : "";
});

      ';
    }
    if ($VectorLayer_File != "NO"){
      $output .= '
        '.$MapName.'.addLayer(vector_kml);
      ';

       $output .= '
        var displayFeatureInfo = function(pixel) {
          var features = [];
          '.$MapName.'.forEachFeatureAtPixel(pixel, function(feature, layer) {
            features.push(feature);
          });
          if (features.length > 0) {
            var name_str, desc_str, info = [];
            var i, ii;
            for (i = 0, ii = features.length; i < ii; ++i) {
              name_str = "<span style=\"font-weight:bold\">" + features[i].get("name") + "</span>";
              desc_str = features[i].get("description");
              name_str = name_str + "<br>" + desc_str;
              info.push(name_str);
            }
            document.getElementById("'.$MapInfoDiv.'").innerHTML = info.join("<br>") || "(unknown)";
            '.$MapName.'.getTarget().style.cursor = "pointer";
          } else {
            document.getElementById("'.$MapInfoDiv.'").innerHTML = "Move the mouse over the icons <br>&nbsp;";
          '.$MapName.'.getTarget().style.cursor = "pointer";
        }
      };
    ';
    $output .= '
      $('.$MapName.'.getViewport()).on("mousemove", function(evt) {
        var pixel = '.$MapName.'.getEventPixel(evt.originalEvent);
        displayFeatureInfo(pixel);
      });
      '.$MapName.'.on("singleclick", function(evt) {displayFeatureInfo(evt.pixel);});';

    }
    $output .= '})(jQuery)';
    $output .= '/* ]]> */';
    $output .= ' </script>';
    $output .= '</div>';
    if ($showMapInfoDiv == 1){
      $div_width = $width-10;
      $output .= '  <div style="margin-top:30px; background-color:#CED8F6; padding:5px; width:'.$width_str.'; height:170px" id="'.$MapInfoDiv.'" class="OSM_Map";>&nbsp;';
      $output .= '  Move the mouse over the icons <br>&nbsp;';
      $output .= '  </div>';
    }
?>
