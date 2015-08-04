<?php
/*
Plugin Name: WP Imgur Plus
Description: Serves your Media Library from imgur.com, changes the library to Imgur and save space in the hard drive! Based in WP Imgur plugin.
Version: 1.0
Author: Weblabor
Author URI: http://www.weblabor.mx
License: GPLv2
*/

require_once(__DIR__ . '/vendor/dsawardekar/arrow/lib/Arrow/ArrowPluginLoader.php');

function wp_imgur_plus_main() {
  $options = array(
    'plugin' => 'WpImgur\Plugin',
    'arrowVersion' => '1.8.0'
  );

  ArrowPluginLoader::load(__FILE__, $options);
}

wp_imgur_plus_main();

add_filter( 'wp_get_attachment_url', 'wpip_changeURL' );

function wpip_changeURL( $value ) {
	$upload_dir = wp_upload_dir(); 
	$orvalue = $value;
	$value = str_replace($upload_dir['baseurl']."/", "", $value);
	if (substr_count($value, "/")<=0) {
		$value = "http://i.imgur.com/".$value;
		return $value;
	}
	return $orvalue;	
}