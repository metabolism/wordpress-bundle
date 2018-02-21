<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Metabolism\WordpressLoader\Helper;

use Metabolism\WordpressLoader\Helper\ACFHelper as ACF;
use Metabolism\WordpressLoader\Model\PostModel as Post,
	Metabolism\WordpressLoader\Model\QueryModel as Query,
	Metabolism\WordpressLoader\Model\TermsModel as Terms,
	Metabolism\WordpressLoader\Model\TermModel as Term;

use Timber\Timber;

/**
 * Class Context
 *
 * Representation of Template Engine context
 * To use it, just @use toArray() method
 *
 * @package Customer\Model
 */
class ContextHelper
{
	public $options;
	protected $context;


	/**
	 * Context constructor.
	 */
	public function __construct($context = []) {

		$this->context = $context;

		$options = new ACF('options');
		$this->options = $options->get();

		$this->addCommon();
	}


	/**
	 * Return Context as Array
	 * @return array
	 */
	public function toArray()
	{
		if( isset($_GET['debug']) and $_GET['debug'] == 'context' and WP_DEBUG )
			$this->debug();

		return $this->context;
	}


	/**
	 * Add some entries directly to general context
	 *
	 * @see timber_context hook
	 *
	 */
	public function addCommon()
	{
		$this->context['options'] = $this->options;
		$this->context['posts_per_page'] = get_option( 'posts_per_page' );

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
		$post = new Post($id);

		if( $callback and is_callable($callback) )
			call_user_func($callback, $post);

		$this->context[$key] = $post;

		return $this->context[$key];
	}


	/**
	 * Add generic entry
	 *
	 * @see Post
	 * @internal param null $id
	 * @param $key
	 * @param $value
	 */
	public function add($key, $value)
	{
		$this->context[$key] = $value;
	}


	/**
	 * Output context Json Formatted
	 *
	 */
	public function debug()
	{
		header('Content-Type: application/json');
		echo json_encode($this->context);
		die();
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

		if( !isset($this->context[$key]) )
			return false;

		$object = $this->context[$key];

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
		if( !isset($this->context[$key]) )
			$this->context[$key] = [];

		if( $found_posts )
		{
			$wp_query = Query::wp_query($args);
			$posts = $wp_query->posts;

			if( !isset($this->context['found_'.$key]) )
				$this->context['found_'.$key] = 0;

			$this->context['found_'.$key] += $wp_query->found_posts;
		}
		else
			$posts = Query::get_posts($args);

		if( $callback && is_callable($callback) )
			array_map($callback, $posts);

		$this->context[$key] = array_merge($this->context[$key], $posts);
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

		$this->context[$key] = Query::get_post($args);

		return $this->context[$key]->ID;
	}


	/**
	 * Add post entry to context with current Post instance
	 * @see Post
	 */
	public function addPagination($args=[])
	{
		$this->context['pagination'] = Timber::get_pagination($args);
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
			$this->context[$key] = Terms::sortHierarchically( Query::get_terms($args));
		else
		{
			$raw_terms = Query::get_terms($args);
			$terms = [];
			foreach ($raw_terms as $term)
				$terms[$term->term_id] = $term;

			$this->context[$key] = $terms;
		}

		return $this->context[$key];
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

		$this->context[$key] = new Term( $term->term_id );

		return $this->context[$key];
	}


	/**
	 * Add push_customized_study entry to context with customized study component content
	 *
	 */
	public function addBreadcrumb($data=[])
	{
		$breadcrumb = [];

		$breadcrumb[] = ['title' => 'Accueil', 'url' => get_home_url()];

		$this->context['breadcrumb'] = array_merge($breadcrumb, $data);
	}
}
