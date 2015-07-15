<?php
/*
Plugin Name: Imgur Plus
Description: Serves your Media Library from imgur.com, changes the library to Imgur and save space in the hard drive! Based in wp-imgur-plus-plus plugin.
Version: 1.0
Author: Carlos Escobar
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

// Add your code and snippets below
add_filter( 'wp_get_attachment_url', 'changeURL' );

function changeURL( $value ) {
	$value = str_replace(get_site_url(), "http://i.imgur.com", $value);
	$value = str_replace("wp-content/uploads/", "", $value);
	return $value;
}