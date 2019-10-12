<?php

namespace Metabolism\WordpressBundle;

use Dflydev\DotAccessData\Data;
use Symfony\Component\Routing\Route,
	Symfony\Component\Routing\RouteCollection;

class Permastruct{

	public $collection;
	private $controller_name, $wp_rewrite, $locale;

	/**
	 * Permastruct constructor.
	 * @param $collection
	 * @param $locale
	 * @param $controller_name
	 */
	public function __construct($collection, $locale, $controller_name)
	{
		global $wp_rewrite;

		$this->collection = $collection;
		$this->controller_name = $controller_name;
		$this->locale = $locale;
		$this->wp_rewrite = $wp_rewrite;

		if( wp_maintenance_mode() )
			$this->addMaintenanceRoute();
		else
			$this->addRoutes();
	}

	/**
	 * Catch all url to display maintenance
	 */
	public function addMaintenanceRoute(){

		$maintenanceController = $this->getControllerName('maintenance');
		$this->addRoute('maintenance', '{req}', ['req'=>".*"], false, $maintenanceController);
	}

	/**
	 * Define all routes from post types and taxonomies
	 */
	public function addRoutes(){

		$this->addRoute('front', '', [], true);

		global $wp_post_types, $wp_taxonomies;

		$registered = [];

		foreach ($wp_post_types as $post_type)
		{
			if( $post_type->public && $post_type->publicly_queryable ){

				if( isset($this->wp_rewrite->extra_permastructs[$post_type->name]) ){

					$base_struct = $this->wp_rewrite->extra_permastructs[$post_type->name]['struct'];
					$translated_slug = get_option( $post_type->name. '_rewrite_slug' );

					if( !empty($translated_slug) )
						$struct = str_replace('/'.$post_type->rewrite['slug'].'/', '/'.$translated_slug.'/', $base_struct);
					else
						$struct = $base_struct;

					$this->addRoute($post_type->name, $struct);
				}

				if( $post_type->has_archive ){

					$base_struct = is_string($post_type->has_archive) ? $post_type->has_archive : $post_type->name;
					$translated_slug = get_option( $post_type->name. '_rewrite_archive' );

					$struct = empty($translated_slug) ? $base_struct : $translated_slug;

					$this->addRoute($post_type->name.'_archive', $struct, [], $this->wp_rewrite->extra_permastructs[$post_type->name]['struct']);
				}

				$registered[] = $post_type->name;
			}
		}

		foreach ($wp_taxonomies as $taxonomy){

			if( $taxonomy->public && $taxonomy->publicly_queryable ){

				if( isset($this->wp_rewrite->extra_permastructs[$taxonomy->name]) ){

					$base_struct = $this->wp_rewrite->extra_permastructs[$taxonomy->name]['struct'];
					$translated_slug = get_option( $taxonomy->name. '_rewrite_slug' );

					if( !empty($translated_slug) )
						$struct = str_replace('/'.$taxonomy->rewrite['slug'].'/', '/'.$translated_slug.'/', $base_struct);
					else
						$struct = $base_struct;


					$this->addRoute($taxonomy->name, $struct, [], $this->wp_rewrite->extra_permastructs[$taxonomy->name]['paged']);

					if( strpos($struct, '/%parent%') !== false )
						$this->addRoute($taxonomy->name.'_parent', str_replace('/%parent%', '', $struct), [], $this->wp_rewrite->extra_permastructs[$taxonomy->name]['paged']);

					$registered[] = $taxonomy->name;
				}
			}
		}

		if( isset($this->wp_rewrite->author_structure) )
			$this->addRoute('author', $this->wp_rewrite->author_structure);

		$translated_search_slug = get_option( 'search_rewrite_slug' );
		$search_post_type_structure = $search_structure = false;

		if( !empty($translated_search_slug) ){

			$search_structure = str_replace($this->wp_rewrite->search_base.'/', $translated_search_slug.'/', $this->wp_rewrite->search_structure);

			if( isset($this->wp_rewrite->search_post_type_structure) )
				$search_post_type_structure = str_replace($this->wp_rewrite->search_base.'/', $translated_search_slug.'/', $this->wp_rewrite->search_post_type_structure);
		}
		else{

			if( isset($this->wp_rewrite->search_structure) )
				$search_structure = $this->wp_rewrite->search_structure;

			if( isset($this->wp_rewrite->search_post_type_structure) )
				$search_post_type_structure = $this->wp_rewrite->search_post_type_structure;
		}

		if( $search_structure )
			$this->addRoute('search', $search_structure, [], true);

		if( $search_post_type_structure )
			$this->addRoute('search_post_type', $search_post_type_structure, [], true);

		$this->addRoute('robots', 'robots.txt', [], false, 'Metabolism\WordpressBundle\Helper\Robots::do');

		if( isset($this->wp_rewrite->page_structure) )
			$this->addRoute('page', $this->wp_rewrite->page_structure, ['pagename'=>'[a-zA-Z0-9]{2}[^/].*']);

		$this->addRoute('site-health', '_site-health', [], false, 'Metabolism\WordpressBundle\Helper\SiteHealth::check');

		$this->addRoute('cache-purge', '_cache/purge', [], false, 'Metabolism\WordpressBundle\Helper\Cache::purge');
		$this->addRoute('cache-clear', '_cache/clear', [], false, 'Metabolism\WordpressBundle\Helper\Cache::clear');
	}


	/**
	 * @param $name
	 * @return string
	 */
	private function getControllerName( $name ){

		$methodName = str_replace('_parent', '', $name);
		$methodName = str_replace(' ', '',lcfirst(ucwords(str_replace('_', ' ', $methodName))));

		return 'App\Controller\\'.$this->controller_name.'::'.$methodName.'Action';
	}

	/**
	 * @param $struct
	 * @return array
	 */
	private function getPaths( $struct ){

		$path = str_replace('%/', '}/', str_replace('/%', '/{', $struct));
		$path = preg_replace('/\%$/', '}/', preg_replace('/^\%/', '/{', $path));
		$path = trim($path, '/');
		$path = !empty($this->locale)? $this->locale.'/'.$path: $path;

		return ['singular'=>$path, 'archive'=>$path.'/'.$this->wp_rewrite->pagination_base.'/{page}'];
	}

	/**
	 * @param $name
	 * @param $struct
	 * @param array $requirements
	 * @param bool $paginate
	 * @param bool $controllerName
	 */
	public function addRoute( $name, $struct, $requirements=[], $paginate=false, $controllerName=false )
	{
		$name = str_replace('_structure', '', $name);

		$controller = $controllerName ? $controllerName : $this->getControllerName($name);
		$paths = $this->getPaths($struct);
		$locale = $this->locale?'.'.$this->locale:'';

		$route = new Route( $paths['singular'], ['_controller'=>$controller], $requirements);
		$this->collection->add($name.$locale, $route);

		if( $paginate && !empty($paths['archive']) )
		{
			$route = new Route( $paths['archive'], ['_controller'=>$controller], $requirements);
			$this->collection->add($name.'_paged'.$locale, $route);
		}
	}
}

/** @var Data $_config */
global $_config;
$collection = new RouteCollection();

if( !isset($_SERVER['SERVER_NAME'] ) && (!isset($_SERVER['WP_INSTALLED']) || !$_SERVER['WP_INSTALLED']) )
    return $collection;

$controller_name = $_config->get('extra_permastructs.controller', 'MainController');

if( $_config->get('multisite') && !$_config->get('multisite.subdomain_install') )
{
	$current_site_id = get_current_blog_id();

	foreach (get_sites() as $site)
	{
		switch_to_blog( $site->blog_id );

		$locale = trim($site->path, '/');
		new Permastruct($collection, $locale, $controller_name);
	}

	switch_to_blog($current_site_id);
}
else{

	new Permastruct($collection, '', $controller_name);
}

return $collection;

