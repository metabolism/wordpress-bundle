<?php

namespace Metabolism\WordpressBundle\Provider;

class Loader{

	public static function all(){

		$providers = scandir(__DIR__);

		foreach($providers as $provider){

			if( !in_array($provider, ['.','..','Loader.php']) )
			{
				$classname = str_replace('.php', '', $provider);
				Loader::load($classname);
			}
		}
	}

	/**
	 * @param $classname
	 */
	public static function load($classname){

		if( ( defined('WP_INSTALLING') && WP_INSTALLING ) || !defined('WPINC') )
			return;

		global $_config;

		if( class_exists('\App\Provider\\'.$classname) )
			$classname = '\App\Provider\\'.$classname;
		else
			$classname = '\Metabolism\WordpressBundle\Provider\\'.$classname;

		new $classname($_config);
	}
}
