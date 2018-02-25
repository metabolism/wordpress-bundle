<?php
/**
 * Plugin Name: WordpressLoader loader
 * Description: Load wordpress yml configuration
 * Version: 1.0.0
 * Author: Metabolism
 * Author URI: http://www.metabolism.fr
 */

$uri = explode('/', $_SERVER['SCRIPT_NAME']);
$page = end($uri);

if( in_array( $page, ['wp-login.php', 'wp-register.php'] ) )
	return;

include __DIR__.'../../src/Plugin/autoload.php';

if( is_admin() )
{
	if( class_exists('\AdminBundle\Controller\AdminController') )
		new \AdminBundle\Controller\AdminController();
	else
		new \Metabolism\WordpressLoader\Controller\AdminController();
}
else
{
	if( class_exists('\FrontBundle\Controller\FrontController') )
		new \FrontBundle\Controller\FrontController();
	else
		new \Metabolism\WordpressLoader\Controller\FrontController();
}
