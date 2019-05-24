<?php

namespace Metabolism\WordpressBundle\Plugin;

class Loader{

	public static function all(){

		$plugins = scandir(__DIR__);

		foreach($plugins as $plugin){

			if( !in_array($plugin, ['.','..','Loader.php']) )
			{
				$classname = str_replace('.php', '', $plugin);
				Loader::load($classname);
			}
		}
	}

	public static function load($classname){

		if( ( defined('WP_INSTALLING') && WP_INSTALLING ) || !defined('WPINC') )
			return;

		global $_config;

		if( class_exists('\App\Plugin\\'.$classname) )
			$classname = '\App\Plugin\\'.$classname;
		else
			$classname = '\Metabolism\WordpressBundle\Plugin\\'.$classname;

		new $classname($_config);
	}
}
