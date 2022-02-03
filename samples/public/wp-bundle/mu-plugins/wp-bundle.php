<?php
/**
 * Plugin Name: Symfony Wordpress Bundle
 * Description: Configure Wordpress using yml and add various plugins
 * Version: 1.0.0
 * Author: Metabolism
 * Author URI: http://www.metabolism.fr
 */

use Metabolism\WordpressBundle\WordpressBundle;

WordpressBundle::loadPlugins();

if( WordpressBundle::isLoginUrl() )
    return;

if( is_admin() )
{
	//load back only controller
	if( class_exists('App\Controller\AdminController') )
		new App\Controller\AdminController();
	else
		new Metabolism\WordpressBundle\Controller\AdminController();
}
else
{
	//load front only controller
	if( class_exists('App\Controller\FrontController') )
		new App\Controller\FrontController();
	else
		new Metabolism\WordpressBundle\Controller\FrontController();
}

//load both case controller
if( class_exists('App\Controller\WordpressController') )
	new App\Controller\WordpressController();
else
	new Metabolism\WordpressBundle\Controller\WordpressController();
