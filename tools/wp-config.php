<?php

/**
 * Wordpress configuration file
 *
 * You may want to edit app/config/wordpress.yml to change :
 *   Database settings
 *   Authentication Keys
 *   Debug mode
 *   Post types
 *   Taxonomies
 *   Admin page removal
 *   Image size
 *   Theme support
 *   Menus
 *   Options page
 *   Page templates
 *
 *  to define other constants, please use a define section in your app/config/local.yml file
 *  see local.sample.yml
 */

// prevent direct access
if( !defined('AUTOLOAD') && !defined('ABSPATH') ){

	header("HTTP/1.0 404 Not Found");
	exit;
}

// load yml config files
include dirname(__DIR__) . '/vendor/metabolism/wordpress-loader/tools/load-config.php';
