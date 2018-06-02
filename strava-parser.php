<?php

/*
Plugin Name: Strava Parser
Plugin URI: http://julien.sidoine.org
Description: Parse a Strava activity and allow to use it directly in a article.
Author: Julien Bras
Version: 0.1
Author URI: http://julien.sidoine.org
*/

require 'vendor/autoload.php';
//require_once 'lib/StaticMap.class.php';
//require_once 'lib/Polyline.php';
use Sunra\PhpSimple\HtmlDomParser;


require 'lib/settings.php';
require 'lib/template.php';
/*
The idea is to parse the strava page content.
activity url : https://www.strava.com/activities/1373023302
stream url that contains long lat info https://www.strava.com/stream/1373023302?streams%5B%5D=latlng
*/


function sparser_filter_content($content)
{
  if(get_option('strava_cf')==null || get_option('mapbox_api')==null) {
    // no config go out
    return $content;
  }
  $strava_url = get_post_meta($GLOBALS['post']->ID, get_option('strava_cf'), true);
  if($strava_url == null) {
    // not strava url go out
    return $content;
  }
  $result = preg_match('/https:\/\/www\.strava\.com\/activities\/(\d+)/i', $strava_url, $matches);
  if($result != 1) {
    //strava url present BUT not the right format
    return '<p>The Strava URL doesn\'t seems to fit the Strava Activity URL (https://www.strava.com/activites/xxxxxx)</p>' .$content;
  }
  // it is a match !
  $activity_id = $matches[1];

  // localisation data url
  // https://www.strava.com/stream/1397553304?streams%5B%5D=resting&streams%5B%5D=latlng&streams%5B%5D=distance&streams%5B%5D=altitude&streams%5B%5D=time&_=1519523585452
  // https://strava.com/stream/1397553304?streams%5B%5D=latlng&streams%5B%5D=altitude
  $stream_url = 'https://strava.com/stream/'.$activity_id.'?streams%5B%5D=latlng&streams%5B%5D=altitude&streams%5B%5D=distance&streams%5B%5D=time&streams%5B%5D=resting';
  $stream = json_decode(file_get_contents($stream_url));

  // allow to grab data in FR (for date mainly)
  /*$opts = array(
    'http'=>array(
      'header'=>"Accept-Language: fr-ca\r\n"
    )
  );

  $context = stream_context_create($opts);
  $dom = HtmlDomParser::file_get_html($strava_url, false, $context);
  $data = array();

  $data['title'] = $dom->find('div.main h1', 0)->plaintext;
  $data['date'] = $dom->find('div.activity-type-date span time', 0);
  //$data['distance'] = '<strong>'.$dom->find('meta[property=fitness:distance:value]', 0)->content.'<span class="unit">'.$dom->find('meta[property=fitness:distance:units]', 0)->content.'</span></strong>';
  //$data['distance'] = $dom->find('li.distance strong', 0);
  $data['elevation'] = '<strong>'.$dom->find('meta[property=fitness:custom_unit_energy:value]', 0)->content.'<span class="unit">m</span></strong>';
  $data['moving_time'] = $dom->find('li.moving-time strong', 0);
  $data['pace'] = $dom->find('li.avg-speed strong', 0);
  $data['calories'] = $dom->find('li.calories strong', 0);
  $data['image'] = $dom->find('div#activity-photos', 0);*/
  return $content.get_after_content($stream, $strava_url);
}

add_filter('the_content', 'sparser_filter_content');