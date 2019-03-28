<?php

namespace Metabolism\WordpressBundle;

use Symfony\Component\Routing\Route,
	Symfony\Component\Routing\RouteCollection;

class Permastruct{

	public $collection;
	private $controller_name, $wp_rewrite, $locale;

	public function __construct($collection, $locale, $controller_name)
	{
		global $wp_rewrite;

		$this->collection = $collection;
		$this->controller_name = $controller_name;
		$this->locale = $locale;
		$this->wp_rewrite = $wp_rewrite;

		$this->addRoutes();
	}

	public function addRoutes(){

		$this->addRoute('front', '');

		global $wp_post_types, $wp_taxonomies;

		$registered = [];

		foreach ($wp_post_types as $post_type)
		{
			if( $post_type->public ){

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

			if( $taxonomy->public ){

				$base_struct = $this->wp_rewrite->extra_permastructs[$taxonomy->name]['struct'];
				$translated_slug = get_option( $taxonomy->name. '_rewrite_slug' );

				if( !empty($translated_slug) )
					$struct = str_replace('/'.$taxonomy->rewrite['slug'].'/', '/'.$translated_slug.'/', $base_struct);
				else
					$struct = $base_struct;

				$this->addRoute($taxonomy->name, $struct, [], $this->wp_rewrite->extra_permastructs[$taxonomy->name]['paged']);

				$registered[] = $taxonomy->name;
			}
		}

		$this->addRoute('author', $this->wp_rewrite->author_structure);

		$translated_search_slug = get_option( 'search_rewrite_slug' );
		$search_post_type_structure = false;

		if( !empty($translated_search_slug) ){

			$search_structure = str_replace($this->wp_rewrite->search_base.'/', $translated_search_slug.'/', $this->wp_rewrite->search_structure);

			if( isset($this->wp_rewrite->search_post_type_structure) )
				$search_post_type_structure = str_replace($this->wp_rewrite->search_base.'/', $translated_search_slug.'/', $this->wp_rewrite->search_post_type_structure);
		}
		else{

			$search_structure = $this->wp_rewrite->search_structure;

			if( isset($this->wp_rewrite->search_post_type_structure) )
				$search_post_type_structure = $this->wp_rewrite->search_post_type_structure;
		}

		$this->addRoute('search', $search_structure, [], true);

		if( $search_post_type_structure )
			$this->addRoute('search_post_type', $search_post_type_structure, [], true);

		$this->addRoute('page', $this->wp_rewrite->page_structure, ['pagename'=>'[^/]{3,}']);
	}


	private function getControllerName( $name ){
		return 'App\Controller\\'.$this->controller_name.'::'.str_replace(' ', '',lcfirst(ucwords(str_replace('_', ' ', $name))).'Action');
	}

	private function getPaths( $struct ){

		$path = str_replace('%/', '}/', str_replace('/%', '/{', $struct));
		$path = preg_replace('/\%$/', '}/', preg_replace('/^\%/', '/{', $path));
		$path = trim($path, '/');
		$path = !empty($this->locale)? $this->locale.'/'.$path: $path;

		return ['singular'=>$path, 'archive'=>$path.'/'.$this->wp_rewrite->pagination_base.'/{page}'];
	}

	public function addRoute( $name, $struct, $requirements=[], $paginate=false )
	{
		$name = str_replace('_structure', '', $name);

		$controller = $this->getControllerName($name);
		$paths = $this->getPaths($struct);
		$locale = $this->locale?'.'.$this->locale:'';

		if( !empty($paths['singular']) or $name == 'front' ){

			$route = new Route( $paths['singular'], ['_controller'=>$controller], $requirements);
			$this->collection->add($name.$locale, $route);
		}

		if( $paginate && !empty($paths['archive']) )
		{
			$route = new Route( $paths['archive'], ['_controller'=>$controller], $requirements);
			$this->collection->add($name.'_paged'.$locale, $route);
		}
	}
}

global $_config;
$controller_name = $_config->get('extra_permastructs.controller', 'MainController');

$collection = new RouteCollection();

if( $_config->get('multisite') && !$_config->get('multisite.multilangue') && !$_config->get('multisite.subdomain_install') )
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

