<?php
/**
 * Plugin Name: WordpressBundle loader
 * Description: Load wordpress yml configuration
 * Version: 1.0.0
 * Author: Metabolism
 * Author URI: http://www.metabolism.fr
 */

$uri = explode('/', $_SERVER['SCRIPT_NAME']);
$page = end($uri);

if( in_array( $page, ['wp-login.php', 'wp-register.php'] ) )
	return;

Metabolism\WordpressBundle\Plugin\Loader::all();

if( is_admin() )
{
	if( class_exists('\App\AdminController') )
		new App\AdminController();
	else
		new Metabolism\WordpressBundle\Controller\AdminController();
}
else
{
	if( class_exists('\App\FrontController') )
		new App\FrontController();
	else
		new Metabolism\WordpressBundle\Controller\FrontController();
}
