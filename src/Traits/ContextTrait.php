<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Metabolism\WordpressBundle\Traits;

use Metabolism\WordpressBundle\Helper\ACFHelper;
use Metabolism\WordpressBundle\Plugin\TermsPlugin;

use Metabolism\WordpressBundle\Entity\Post,
	Metabolism\WordpressBundle\Entity\Query,
	Metabolism\WordpressBundle\Entity\Term,
	Metabolism\WordpressBundle\Entity\Menu;

use Timber\Timber;

/**
 * Trait ContextTrait
 *
 * Representation of Template Engine context
 * To use it, just @use toArray() method
 *
 * @package WordpressBundle\Traits
 */
Trait ContextTrait
{
	public $options;


	/**
	 * Context constructor.
	 */
	public function __construct()
	{
		$this->loadWordpress();

		$options = new ACFHelper('options');
		$this->options = $options->get();

		$this->addSite();
		$this->addMenu();
		$this->addCurrentPost();
	}


	private function loadWordpress()
	{
		include EDITION_URI.'/wp-blog-header.php';
	}


	protected function getOutput($function, $args=[])
	{
		ob_start();
		call_user_func_array($function, $args);
		$data = ob_get_contents();
		ob_end_clean();

		return $data;
	}


	public function addSite()
	{
		global $_config;

		$blog_language = get_bloginfo('language');
		$language = explode('-', $blog_language);

		if( function_exists('wpml_get_active_languages_filter') )
			$languages = wpml_get_active_languages_filter('','skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str');
		else
			$languages = [];

		$this->data = [
			'debug'            => WP_DEBUG,
			'environment'      => $_config->get('environment', 'production'),
			'locale'           => count($language) ? $language[0] : 'en',
			'language'         => $blog_language,
			'languages'        => $languages,
			'is_admin'         => current_user_can('manage_options'),
			'body_class'       => $blog_language . ' ' . implode(' ', get_body_class()),
			'home_url'         => home_url(),
			'maintenance_mode' => wp_maintenance_mode(),
			'charset'          => get_bloginfo('charset')
		];

		if (class_exists('WooCommerce'))
		{
			$wcProvider = WooCommerceProvider::getInstance();
			$wcProvider->globalContext($this->data);
		}

		$this->data['system'] = [
			'head'   => $this->getOutput('wp_head'),
			'footer' => $this->getOutput('wp_footer')
		];

		$wp_title = wp_title(' ', false);

		$this->data['page_title']  = empty($wp_title) ? get_bloginfo('name') : $wp_title;

		$this->data['options'] = $this->options;
		$this->data['posts_per_page'] = get_option( 'posts_per_page' );
	}


	public function addMenu()
	{
		$menus = get_registered_nav_menus();
		$this->data['menus'] = [];

		foreach ( $menus as $location => $description )
			$this->data['menus'][$location] = new Menu($location);
	}


	public function addCurrentPost()
	{
		if( is_single() or is_page() )
			$this->addPost();
		elseif( is_archive() )
			$this->addPosts();
	}


	/**
	 * Add post entry to context with current Post instance
	 *
	 * @see Post
	 * @param null $id
	 * @param string $key
	 * @return mixed
	 */
	public function addPost($id = null, $key='post', $callback=false)
	{
		if( is_null($id) )
			$id = get_the_ID();

		$post = new Post($id);

		if( $callback and is_callable($callback) )
			call_user_func($callback, $post);

		$this->data[$key] = $post;

		return $this->data[$key];
	}


	/**
	 * Add post entry to context with current Post instance
	 *
	 * @see   Post
	 * @param $key
	 * @param $item
	 * @param $term
	 * @return bool
	 */
	public function sortHierarchicallyByTerm($key, $item, $term)
	{
		$sorted_posts = [];

		if( !isset($this->data[$key]) )
			return false;

		$object = $this->data[$key];

		if( !isset($object->$item) )
			return false;

		$posts = &$object->$item;

		foreach ($posts as $post )
		{
			$terms = get_the_terms($post->id, $term);

			if( !count($terms) )
				return false;

			if( !isset($sorted_posts[$terms[0]->term_id]) )
				$sorted_posts[$terms[0]->term_id] = ['name'=>$terms[0]->name, 'posts'=>[]];

			$sorted_posts[$terms[0]->term_id]['posts'][] = $post;
		}

		$posts = $sorted_posts;

		return true;
	}


	/**
	 * Add post entry to context with current Post instance
	 *
	 * @see Post
	 * @param array  $args
	 * @param string $key
	 */
	public function addPosts($args=[], $key='posts', $found_posts=false, $callback=false)
	{
		if( !isset($this->data[$key]) )
			$this->data[$key] = [];

		if( $found_posts )
		{
			$wp_query = Query::wp_query($args);
			$posts = $wp_query->posts;

			if( !isset($this->data['found_'.$key]) )
				$this->data['found_'.$key] = 0;

			$this->data['found_'.$key] += $wp_query->found_posts;
		}
		else
			$posts = Query::get_posts($args);

		if( $callback && is_callable($callback) )
			array_map($callback, $posts);

		$this->data[$key] = array_merge($this->data[$key], $posts);
	}


	/**
	 * Add post entry to context with current Post instance
	 * @see Post
	 */
	public function addFeatured($args=[], $key = 'featured_post')
	{
		$args = array_merge([
			'meta_query' => [[
				'key' => 'featured',
				'value' => true,
			]]
		], $args);

		$this->data[$key] = Query::get_post($args);

		return $this->data[$key]->ID;
	}


	/**
	 * Add post entry to context with current Post instance
	 * @see Post
	 */
	public function addPagination($args=[])
	{
		$this->data['pagination'] = Timber::get_pagination($args);
	}


	/**
	 * Add post entry to context with current Post instance
	 * @see Post
	 */
	public function addTerms($args=[], $key=false, $sort=true)
	{
		if( !$key )
			$key = $args['taxonomy'];

		if( $sort )
			$this->data[$key] = TermsPlugin::sortHierarchically( Query::get_terms($args));
		else
		{
			$raw_terms = Query::get_terms($args);
			$terms = [];
			foreach ($raw_terms as $term)
				$terms[$term->term_id] = $term;

			$this->data[$key] = $terms;
		}

		return $this->data[$key];
	}


	/**
	 * Add post entry to context with current Post instance
	 * @see Post
	 */
	public function addTerm($field, $value, $taxonomy='', $key=false)
	{
		$term = get_term_by($field, $value, $taxonomy);

		if( !$term )
			return false;

		if( !$key )
			$key = $field;

		$this->data[$key] = new Term( $term->term_id );

		return $this->data[$key];
	}


	/**
	 * Add push_customized_study entry to context with customized study component content
	 *
	 */
	public function addBreadcrumb($data=[])
	{
		$breadcrumb = [];

		$breadcrumb[] = ['title' => 'Accueil', 'url' => get_home_url()];

		$this->data['breadcrumb'] = array_merge($breadcrumb, $data);
	}
}
