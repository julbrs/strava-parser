<?php

function register_plugin_styles() {
  // Register a personalized stylesheet.
  wp_register_style( 'strava_parser1', plugins_url('strava-parser/css/strava-parser.css') );
  wp_register_style( 'strava_parser2', 'https://unpkg.com/leaflet@1.3.1/dist/leaflet.css' );
  wp_register_style( 'strava_parser3', plugins_url('strava-parser/css/Leaflet.Elevation-0.0.2.css') );
  wp_register_style( 'strava_parser4', 'https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/leaflet.fullscreen.css' );
  wp_enqueue_style( 'strava_parser1' );
  wp_enqueue_style( 'strava_parser2' );
  wp_enqueue_style( 'strava_parser3' );
  wp_enqueue_style( 'strava_parser4' );
  wp_enqueue_script( 'script1', 'http://d3js.org/d3.v3.min.js', array(), 1.0, false);
  wp_enqueue_script( 'script2', 'https://unpkg.com/leaflet@1.3.1/dist/leaflet.js', array(), 1.0, false);
  wp_enqueue_script( 'script3', plugins_url('strava-parser/js/Leaflet.Elevation-0.0.2.min.js'), array(), 1.0, false);
  wp_enqueue_script( 'script4', 'https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/Leaflet.fullscreen.min.js', array(), 1.0, false);
}

if (!is_admin() ) {
  // Register style sheet.
  add_action( 'wp_enqueue_scripts', 'register_plugin_styles' );
}

function get_after_content($stream, $strava_url) {
  $mapbox = get_option('mapbox_api');
  $url = plugins_url('strava-parser/');

  $l = '';
  $start = '';
  $end = '';
  $elevation = 0;
  $resting = 0;
  $currentstepid = 0;
  $STEPSIZE = 69;
  foreach ($stream->latlng as $id => $point) {
    if($l!='') {
      $l .= ',';
      $end = '['.$point[0].','.$point[1].']';
    }
    else {
      $start = '['.$point[0].','.$point[1].']';
    }
    $l .= '['.$point[1].','.$point[0].','.$stream->altitude[$id].']';
    $delta = $stream->altitude[$id] - $stream->altitude[$id-1];
    if($id != 0 && $delta > 0 ) {
      //$elevation += $delta;
    }
    if($id != 0 && $id % 2 == 0) {

      $delta = $stream->altitude[$id] - $stream->altitude[$currentstepid];
      if($delta > 0) {
        $elevation += $delta;
      }
      $currentstepid = $id;
    }
    if($id != 0 && $stream->resting[$id]) {
      $resting += $stream->time[$id] - $stream->time[$id-1];
    }
  }
  // compute distance with stream data ! take last element /1000 to have in km
  $distance = round(end($stream->distance)/1000, 1);
  $elevation = round($elevation);

  // compute time with stream data ! take last element and parse it
  $seconds = end($stream->time) - $resting;
  // gmadate is messy when there is more than 24h. Yeah I know who is running more than 24h you ask ?
  $time = floor($seconds / 3600) . gmdate(":i:s", $seconds % 3600);

  return <<<HTML
<div id="stravabox">
  <!-- <a target="_blank" href="{$strava_url}">
    <img id="strava-image" src="{$data[image]}" alt="">
  </a>-->
<div id="map"></div>

<table id="strava-data" class="g1d-navbar">
  <thead>
    <tr>
      <th>Durée</th>
      <th>Distance</th>
      <th>Dénivelé</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>{$time}</strong></td>
      <td><strong>{$distance} <span class="unit">km</span></strong></td>
      <td><strong>{$elevation} <span class="unit">m</span></strong></td>
    </tr>
    <tr>
      <td colspan="3">
      <a target="_blank" href="{$strava_url}">Lien vers l'activité Strava</a>
      </td>
    </tr>
  </tbody>
</table>



    <script type="text/javascript">

      var map = L.map('map', {
        //fullscreenControl: true,
        fullscreenControl: {
          pseudoFullscreen: true // if true, fullscreen to page width and height
        }
      }).setView([51.505, -0.09], 13);

      L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
          attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
          maxZoom: 18,
          id: 'mapbox.streets',
          accessToken: '{$mapbox}'
      }).addTo(map);

      var geojson = {"name":"NewFeatureType","type":"FeatureCollection","features":[
        {"type":"Feature","geometry":{"type":"LineString","coordinates":[{$l}]},"properties":null}
        ]}
        ;

      var el = L.control.elevation({
        position: "bottomright",
      	theme: "steelblue-theme", //default: lime-theme
      	width: 400,
      	height: 120,
      	margins: {
      		top: 10,
      		right: 20,
      		bottom: 30,
      		left: 50
      	},
      	useHeightIndicator: true, //if false a marker is drawn at map position
      	interpolation: "linear", //see https://github.com/mbostock/d3/wiki/SVG-Shapes#wiki-area_interpolate
      	hoverNumber: {
      		decimalsX: 1, //decimals on distance (always in km)
      		decimalsY: 0, //deciamls on hehttps://www.npmjs.com/package/leaflet.coordinatesight (always in m)
      		formatter: undefined //custom formatter function may be injected
      	},
      	xTicks: undefined, //number of ticks in x axis, calculated by default according to width
      	yTicks: undefined, //number of ticks on y axis, calculated by default according to height
      	collapsed: false,  //collapsed mode, show chart on click or mouseover
      	imperial: false    //display imperial units instead of metric
      });
      el.addTo(map);
      var gjl = L.geoJson(geojson,{
          onEachFeature: el.addData.bind(el)
      }).addTo(map);

      var marker = L.marker({$start}, {
          //icon: L.marker.icon({'marker-symbol': 'post', 'marker-color': '0044FF'}),
          title: 'Début'
      }).addTo(map);

      var marker2 = L.marker({$end}, {
          //icon: L.marker.icon({'marker-symbol': 'post', 'marker-color': '0044FF'}),
          title: 'Fin'
      }).addTo(map);

      map.fitBounds(gjl.getBounds());
    </script>
</div>
HTML;
}