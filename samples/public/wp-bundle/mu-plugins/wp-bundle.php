<?php
/**
 * Plugin Name: Symfony Wordpress Bundle Loader
 * Description: Load Wordpress in Symfony
 * Version: 2.2.0
 * Author: Metabolism
 */

use Metabolism\WordpressBundle\WordpressBundle;

WordpressBundle::loadPlugins();

if( WordpressBundle::isLoginUrl() ){

    //load login only action
    if( class_exists('App\Action\LoginAction') )
        new App\Action\LoginAction();
    else
        new Metabolism\WordpressBundle\Action\LoginAction();

    return;
}

if( is_admin() )
{
	//load back only action
	if( class_exists('App\Action\AdminAction') )
		new App\Action\AdminAction();
	else
		new Metabolism\WordpressBundle\Action\AdminAction();
}
else
{
	//load front only action
	if( class_exists('App\Action\FrontAction') )
		new App\Action\FrontAction();
	else
		new Metabolism\WordpressBundle\Action\FrontAction();
}

//load both case action
if( class_exists('App\Action\WordpressAction') )
	new App\Action\WordpressAction();
else
	new Metabolism\WordpressBundle\Action\WordpressAction();
