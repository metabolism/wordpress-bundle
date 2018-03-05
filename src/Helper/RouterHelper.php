<?php

namespace Metabolism\WordpressBundle\Helper;

class RouterHelper
{

	public static function rewrite( $routes )
	{
		$routeIterator = $routes->getIterator();

		global $_config;

		foreach ($routeIterator as $name => $route)
		{
			if( $wordpress = $route->getDefault('wordpress') )
			{
				$wordpress = explode(':', $wordpress);
				$path = explode('/', $route->getPath());

				$id  = array_shift($path);
				$key = $wordpress[0].'.'.$id;

				if( $wordpress[1] == 'single' )
					$key .= 'rewrite.slug';
				if( $wordpress[1] == 'archive' )
					$key .= 'has_archive';

				$rewrite_slug = $_config->get($key, $id);

				if( $rewrite_slug != $id )
				{
					$path[0] = $rewrite_slug;
					$route->setPath( implode('/', $path));
				}
			}
		}
	}
}
