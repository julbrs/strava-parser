<?php

/*
Plugin Name: Strava Parser
Plugin URI: http://julien.sidoine.org
Description: Parse a Strava activity and allow to use it directly in a article.
Author: Julien Bras
Version: 0.2
Author URI: http://julien.sidoine.org
*/

require 'vendor/autoload.php';
//require_once 'lib/StaticMap.class.php';
//require_once 'lib/Polyline.php';
use Sunra\PhpSimple\HtmlDomParser;
use phpGPX\Models\GpxFile;
use phpGPX\Models\Link;
use phpGPX\Models\Metadata;
use phpGPX\Models\Point;
use phpGPX\Models\Segment;
use phpGPX\Models\Track;

require 'lib/settings.php';
require 'lib/template.php';
/*
The idea is to parse the strava page content.
activity url : https://www.strava.com/activities/1373023302
stream url that contains long lat info https://www.strava.com/stream/1373023302?streams%5B%5D=latlng
*/


function sparser_filter_content($content) {
  if(get_option('mapbox_api')==null) {
    // no config go out
    return $content;
  }

  $strava_url = get_post_meta($GLOBALS['post']->ID, get_option('strava_cf'), true);
  if($strava_url == null) {
    // not strava url go out
    return;
  }

  $wp_upload_dir = wp_upload_dir();
  $file = $wp_upload_dir['basedir'].'/gpx/'.$GLOBALS['post']->ID.'.gpx';
  if (!file_exists($file)) {
    // no gpx file go out
    return $content;
  }

  return $content.get_after_content($strava_url);
}

function grab_strava_gpx($post_id) {
  if(get_option('strava_cf')==null) {
    // no config go out
    return;
  }
  $strava_url = get_post_meta($post_id, get_option('strava_cf'), true);
  if($strava_url == null) {
    // not strava url go out
    return;
  }
  $result = preg_match('/https:\/\/www\.strava\.com\/activities\/(\d+)/i', $strava_url, $matches);
  if($result != 1) {
    //strava url present BUT not the right format
    return;
    //return '<p>The Strava URL doesn\'t seems to fit the Strava Activity URL (https://www.strava.com/activites/xxxxxx)</p>' .$content;
  }
  // it is a match !
  $activity_id = $matches[1];

  // localisation data url
  // https://www.strava.com/stream/1397553304?streams%5B%5D=resting&streams%5B%5D=latlng&streams%5B%5D=distance&streams%5B%5D=altitude&streams%5B%5D=time&_=1519523585452
  // https://strava.com/stream/1397553304?streams%5B%5D=latlng&streams%5B%5D=altitude
  $stream_url = 'https://strava.com/stream/'.$activity_id.'?streams%5B%5D=latlng&streams%5B%5D=altitude&streams%5B%5D=distance&streams%5B%5D=time&streams%5B%5D=resting';
  $stream = json_decode(file_get_contents($stream_url));

  // Creating sample link object for metadata
  $link 							= new Link();
  $link->href 					= get_permalink($post_id);;
  $link->text 					= 'Strava Activity';

  $gpx_file 						= new GpxFile();
  $gpx_file->metadata 			= new Metadata();
  $gpx_file->metadata->time 		= new \DateTime();
  $gpx_file->metadata->description = "My pretty awesome GPX file, created using phpGPX library!";
  $gpx_file->metadata->links[] 	= $link;

  // Creating track
  $track 							= new Track();
  $track->name 					= sprintf(get_the_title($post_id));
  $track->type 					= 'RUN';
  $track->source 					= sprintf("Strava");

  $segment 						= new Segment();
  $date = new DateTime();
  foreach ($stream->latlng as $id => $pt) {
  	// Creating trackpoint
  	$point 						 = new Point(Point::TRACKPOINT);
  	$point->latitude 	 = $pt[0];
  	$point->longitude  = $pt[1];
  	$point->elevation  = $stream->altitude[$id];
  	$point->time 			 = new \DateTime('+'.$stream->time[$id].' seconds');
  	$segment->points[] = $point;
  }

  $track->segments[] 				= $segment;
  $track->recalculateStats();
  $gpx_file->tracks[] 			= $track;

  // GPX output
  $wp_upload_dir = wp_upload_dir();
  //var_dump($wp_upload_dir['basedir']."/gpx/CreatingFileFromScratchExample.gpx");
  $gpx_dir = $wp_upload_dir['basedir'].'/gpx';
  if ( ! file_exists( $gpx_dir ) ) {
    wp_mkdir_p( $gpx_dir );
  }
  $gpx_file->save($wp_upload_dir['basedir'].'/gpx/'.$post_id.'.gpx', \phpGPX\phpGPX::XML_FORMAT);
}
add_action( 'save_post', 'grab_strava_gpx' );

add_filter('the_content', 'sparser_filter_content');