<?php

if( defined('WPINC') ){

	global $_config;

	$plugins = scandir(__DIR__);
	foreach($plugins as $plugin){

		if( !in_array($plugin, ['.','..','autoload.php']) )
		{
			$classname = str_replace('.php', '', $plugin);

			if( class_exists('\AdminBundle\Plugin\\'.$classname) )
				$classname = '\AdminBundle\Plugin\\'.$classname;
			else
				$classname = '\Metabolism\WordpressLoader\Plugin\\'.$classname;

			new $classname($_config);
		}
	}
}
