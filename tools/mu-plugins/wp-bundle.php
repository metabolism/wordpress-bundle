<?php
/**
 * Plugin Name: Wordpress Bundle
 * Description: Configure Wordpress using yml and add various plugins
 * Version: 1.0.0
 * Author: Metabolism
 * Author URI: http://www.metabolism.fr
 */

use Metabolism\WordpressBundle\Plugin\Loader;

$uri = explode('/', $_SERVER['SCRIPT_NAME']);
$page = end($uri);

if( in_array( $page, ['wp-login.php', 'wp-signup.php'] ) )
{
	Loader::load('UrlPlugin');
	return;
}

Loader::all();

if( is_admin() )
{
	if( class_exists('App\Controller\AdminController') )
		new App\Controller\AdminController();
	else
		new Metabolism\WordpressBundle\Controller\AdminController();
}
else
{
	if( class_exists('App\Controller\FrontController') )
		new App\Controller\FrontController();
	else
		new Metabolism\WordpressBundle\Controller\FrontController();
}
