<?php

/**
 * Wordpress configuration file
 *
 * You may want to edit config/wordpress.yml to change :
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
 */

// prevent direct access
if( !class_exists('App') && !defined('ABSPATH') ){

	header("HTTP/1.0 404 Not Found");
	exit;
}


use Symfony\Component\Dotenv\Dotenv;
use Metabolism\WordpressBundle\Loader\ConfigLoader;


if( !class_exists('App') )
	require dirname(__DIR__).'/vendor/autoload.php';


if (!isset($_SERVER['APP_ENV'])) {
	if (!class_exists(Dotenv::class)) {
		throw new \RuntimeException('APP_ENV environment variable is not defined. You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
	}
	(new Dotenv())->load(__DIR__.'/../.env');
}


$loader = new ConfigLoader();
$loader->import( dirname(__DIR__).'/config/wordpress.yml' );

$table_prefix  = $loader->get('database.prefix', 'wp_');

require_once(ABSPATH . 'wp-settings.php');
