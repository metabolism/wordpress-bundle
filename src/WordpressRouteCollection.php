<?php

namespace Metabolism\WordpressBundle;

use Metabolism\WordpressBundle\Helper\WordpressHelper as Wordpress;

use Symfony\Component\Routing\Route,
	Symfony\Component\Routing\RouteCollection;

class WordpressRouteCollection extends RouteCollection
{

	public function __construct()
	{
		Wordpress::load();
	}

	public function addCollection(RouteCollection $collection)
	{
		global $wp_rewrite, $_config;

		$controller_name = $_config->get('wp_controller', 'MainController');
		$locale = $_config->get('multisite.multilangue') ? '{_locale}': '';

		foreach ($wp_rewrite->extra_permastructs as $name=>$permastruct)
		{
			if( $permastruct['with_front'])
			{
				$controller = str_replace(' ', '',lcfirst(ucwords(str_replace('_', ' ', $name))).'Action');
				$route = new Route( str_replace('%', '}', $locale.str_replace('/%', '/{', $permastruct['struct'])), ['_controller'=>'App\Controller\\'.$controller_name.'::'.$controller]);
				parent::add('wp_'.$name, $route);
			}
		}

		parent::addCollection($collection);
	}
}
