<?php

/**
 * Wordpress configuration file
 *
 * You may want to edit config/wordpress.yml to change :
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
 *  to define other constants, please use a define section in your config/local.yml file
 *  see local.sample.yml
 */

// prevent direct access
if( !defined('AUTOLOAD') && !defined('ABSPATH') ){

	header("HTTP/1.0 404 Not Found");
	exit;
}

// load configuration from wordpress-bundle
include dirname(__DIR__) . '/vendor/metabolism/wordpress-bundle/tools/load-config.php';
