<?php

namespace Metabolism\WordpressBundle;

use Metabolism\WordpressBundle\Helper\WordpressHelper as Wordpress;

use Symfony\Component\Routing\Route,
	Symfony\Component\Routing\RouteCollection;

global $wp_rewrite, $wp_post_types, $_config;

$controller_name = $_config->get('extra_permastructs.controller', 'MainController');
$locale = $_config->get('multisite.multilangue') ? '{_locale}': '';

$collection = new RouteCollection();

$addRoute = function( $name, $struct, $is_archive=false ) use($controller_name, $locale, $collection, $wp_rewrite)
{
	$name = str_replace('_structure', '', $name);

	$controller = 'App\Controller\\'.$controller_name.'::'.str_replace(' ', '',lcfirst(ucwords(str_replace('_', ' ', $name))).'Action');
	$path = str_replace('%/', '}', str_replace('/%', '/{', $struct));
	$path = preg_replace('/\%$/', '}', preg_replace('/^\%/', '{', $path));

	$route = new Route( $locale.$path, ['_controller'=>$controller]);
	$collection->add('wp_'.$name, $route);

	if( $is_archive )
	{
		$route = new Route( $locale.$path.'/'.$wp_rewrite->pagination_base.'/{page}', ['_controller'=>$controller]);
		$collection->add('wp_'.$name.'_paged', $route);
	}
};

$addRoute('front', '/');

foreach ($wp_rewrite->extra_permastructs as $name=>$permastruct)
{
	if( $permastruct['with_front'])
		$addRoute($name, $permastruct['struct']);
}

foreach ($wp_post_types as $wp_post_type)
{
	if( $wp_post_type->has_archive )
		$addRoute($wp_post_type->name.'_archive', $wp_post_type->has_archive, true);
}

foreach (['author_structure', 'search_structure', 'page_structure'] as $name)
{
	$addRoute($name, $wp_rewrite->$name);
}

return $collection;

