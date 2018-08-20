<?php

namespace Metabolism\WordpressBundle\Plugin;

class Loader{

	public static function all(){

		if( defined('WP_INSTALLING') and WP_INSTALLING )
			return;

		if( defined('WPINC') ){

			global $_config;

			$plugins = scandir(__DIR__);
			foreach($plugins as $plugin){

				if( !in_array($plugin, ['.','..','autoload.php']) )
				{
					$classname = str_replace('.php', '', $plugin);

					if( class_exists('App\Plugin\\'.$classname) )
						$classname = 'App\Plugin\\'.$classname;
					else
						$classname = 'Metabolism\WordpressBundle\Plugin\\'.$classname;

					new $classname($_config);
				}
			}
		}
	}
}
