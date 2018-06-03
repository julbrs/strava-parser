<?php

function register_plugin_styles() {
  // Register a personalized stylesheet.
  wp_register_style( 'strava_parser1', plugins_url('strava-parser/css/strava-parser.css') );
  wp_register_style( 'strava_parser2', 'https://unpkg.com/leaflet@1.3.1/dist/leaflet.css' );
  wp_register_style( 'strava_parser3', plugins_url('strava-parser/css/leaflet.elevation-0.0.4.css') );
  wp_register_style( 'strava_parser4', 'https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/leaflet.fullscreen.css' );
  wp_enqueue_style( 'strava_parser1' );
  wp_enqueue_style( 'strava_parser2' );
  wp_enqueue_style( 'strava_parser3' );
  wp_enqueue_style( 'strava_parser4' );
  wp_enqueue_script( 'script1', 'http://d3js.org/d3.v3.min.js', array(), 1.0, false);
  wp_enqueue_script( 'script2', 'https://unpkg.com/leaflet@1.3.1/dist/leaflet.js', array(), 1.0, false);
  wp_enqueue_script( 'script3', plugins_url('strava-parser/js/leaflet.elevation-0.0.4.min.js'), array(), 1.0, false);
  wp_enqueue_script( 'script4', 'https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/Leaflet.fullscreen.min.js', array(), 1.0, false);
  wp_enqueue_script( 'script5', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet-gpx/1.3.1/gpx.min.js', array(), 1.0, false);
}

if (!is_admin() ) {
  // Register style sheet.
  add_action( 'wp_enqueue_scripts', 'register_plugin_styles' );
}

function get_after_content() {
  $mapbox = get_option('mapbox_api');
  $wp_upload_dir = wp_upload_dir();
  $gpxurl = $wp_upload_dir['baseurl'].'/gpx/'.get_the_ID().'.gpx';

  return <<<HTML
<div id="stravabox">
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
      <td><strong><span id="time"></span></strong></td>
      <td><strong ><span id="distance"></span> <span class="unit">km</span></strong></td>
      <td><strong><span id="elevation"></span> <span class="unit">m</span></strong></td>
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
      // var gjl = L.geoJson(geojson,{
      //     onEachFeature: el.addData.bind(el)
      // }).addTo(map);

      // adding gpx map
      var gpx = '{$gpxurl}'; // URL to your GPX file or the GPX itself
      var g = new L.GPX(gpx, {
        async: true,
        marker_options: {

          endIconUrl: 'images/pin-icon-end.png',
          shadowUrl: 'images/pin-shadow.png'
        }
      }).on('loaded', function(e) {
        map.fitBounds(e.target.getBounds());
        document.getElementById("time").innerHTML = e.target.get_duration_string(e.target.get_total_time(), true);
        document.getElementById("distance").innerHTML = Math.round(e.target.get_distance()/1000);
        document.getElementById("elevation").innerHTML = Math.round(e.target.get_elevation_gain());

      }).on('addline',function(e){
        el.addData(e.line);
      });
      g.addTo(map);


    </script>
</div>
HTML;
}