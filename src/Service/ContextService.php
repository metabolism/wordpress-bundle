<?php

namespace Metabolism\WordpressBundle\Service;

use Metabolism\WordpressBundle\Entity\Menu;
use Metabolism\WordpressBundle\Entity\Post;
use Metabolism\WordpressBundle\Entity\Term;
use Metabolism\WordpressBundle\Entity\User;
use Metabolism\WordpressBundle\Factory\PostFactory;
use Metabolism\WordpressBundle\Factory\TermFactory;
use Metabolism\WordpressBundle\Repository\PostRepository;
use Metabolism\WordpressBundle\Repository\TermRepository;

/**
* @deprecated deprecated since version 2.0
*/
class ContextService
{
	protected $data;

    private $current_user;

    private $postRepository;
    private $termRepository;

    private $paginationService;
    private $breadcrumbService;

    /**
     * Context constructor.
     */
    public function __construct(PostRepository $postRepository, TermRepository $termRepository, PaginationService $paginationService, BreadcrumbService $breadcrumbService){

        $this->postRepository = $postRepository;
        $this->termRepository = $termRepository;
        $this->paginationService = $paginationService;
        $this->breadcrumbService = $breadcrumbService;

        $this->addCurrentUser();
        $this->addContent();
    }

    /**
     * Return function echo
     * @param $function
     * @param array $args
     * @return string
     */
    protected function getOutput($function, $args=[]){

        ob_start();
        call_user_func_array($function, $args);
        $data = ob_get_contents();
        ob_end_clean();

        return $data;
    }

    /**
     * Get current post
     * @return Post|bool
     */
    public function getPost(){

        return $this->get('post');
    }


    /**
     * Get current post
     * @return Term|bool
     */
    public function getTerm(){

        return $this->get('term');
    }


    /**
     * Get current posts
     * @return Post[]|bool
     */
    public function getPosts(){

        return $this->get('posts');
    }


    /**
     * Get menus
     * @return Menu[]|bool
     */
    public function getMenus(){

        return $this->get('menu');
    }


    /**
     * Get options
     * @return array
     */
    public function getOptions(){

        return $this->get('options');
    }


    /**
     * Get current user
     * @return User|bool
     */
    protected function addCurrentUser(){

        if(is_null($this->current_user)){

            $current_user_id = get_current_user_id();
            $this->current_user = $current_user_id ? new User($current_user_id) : false;

            $this->data['current_user'] = $this->current_user;
        }

        return $this->current_user;
    }

    /**
     * Add list of all public wordpress post, page and custom post
     * @param array $args see https://codex.wordpress.org/Class_Reference/WP_Query#Parameters
     * @return array
     */
    public function addSitemap($args=[])
    {
        $post_types = get_post_types(['public'=> true]);
        $post_types = array_diff($post_types, ['attachment', 'revision', 'nav_menu_item']);

        $sitemap = [];

        $query = array_merge(['post_type' => $post_types, 'posts_per_page' => -1], $args);

        $query = new \WP_Query($query);

        if( isset($query->posts) && is_array($query->posts) )
        {
            foreach ($query->posts as $post)
            {
                $template = get_page_template_slug($post);
                $sitemap[] = [
                    'link'=> get_permalink($post),
                    'template' => empty($template)?'default':$template,
                    'name' => $post->post_name,
                    'type' => $post->post_type,
                    'modified' => $post->post_modified,
                    'title' => $post->post_title,
                    'ID' => $post->ID
                ];
            }
        }

        $this->data['sitemap'] = $sitemap;

        return $sitemap;
    }


    /**
     * @return User|bool
     */
    public function getCurrentUser(){

        return $this->current_user;
    }


    /**
     * Get default wordpress data
     * @return Post|array|bool
     */
    protected function addContent(){

        if( (is_single() || is_page()) && !is_attachment() ) {

            return $this->addPost();
        }
        elseif( is_archive() ) {

            return ['term'=>$this->addTerm(), 'posts'=>$this->addPosts()];
        }
        elseif( is_search() || is_home() ) {

            return $this->addPosts();
        }

        return false;
    }


    /**
     * Add post to context from id
     *
     * @param null $id
     * @param string $key
     * @param callable|bool $callback
     * @return Post|bool
     */
    public function addPost($id = null, $key='post', $callback=false){

        if( is_null($id) )
            $id = get_the_ID();

        if( $id ) {

            $post = PostFactory::create($id);

            if( $callback && is_callable($callback) )
                call_user_func($callback, $post);

            $this->data[$key] = $post;

            return $this->data[$key];
        }

        return false;
    }


    /**
     * Add term entry to context from id or object with field, value and taxonomy to perform a get_term_by
     *
     * @see Post
     * @param int|object $id
     * @param string $key
     * @param callable|bool $callback
     * @return Term|bool
     */
    public function addTerm($id = null, $key='term', $callback=false){

        if( is_null($id) ) {

            global $wp_query;
            $cat_obj = $wp_query->get_queried_object();

            if( $cat_obj && isset($cat_obj->term_id))
                $id = $cat_obj->term_id;
        }

        if( $id ) {

            if( is_array($id) && isset($id['field'], $id['value'], $id['taxonomy']) ){

                $term = get_term_by($id['field'], $id['value'], $id['taxonomy']);

                if( $term )
                    $id = $term->term_id;
            }

            $term = TermFactory::create($id);

            if( !is_wp_error($term) && $callback && is_callable($callback) )
                call_user_func($callback, $term);

            $this->data[$key] = is_wp_error($term) ? false: $term;

            return $this->data[$key];
        }

        return false;
    }


    /**
     * QueryHelper posts
     *
     * @see Post
     * @param array $args see https://codex.wordpress.org/Class_Reference/WP_Query#Parameters,
     * added output=array|object, override=bool and found_posts=bool
     * @param bool|string $key the key name to store data
     * @param callable|bool $callback execute a function for each result via array_map
     * @return Post[]
     */
    public function addPosts($args=[], $key='posts', $callback=false){

        global $wp_query;

        if( !isset($args['post_type']) )
            $args = array_merge($wp_query->query, $args);

        $raw_posts = $this->postRepository->findBy($args);
        $posts = [];

        if( isset($args['found_posts']) && $args['found_posts']) {

            if( !isset($this->data['found_'.$key]) )
                $this->data['found_'.$key] = $this->postRepository->count($args);
            else
                $this->data['found_'.$key] += $this->postRepository->count($args);
        }

        if( $callback && is_callable($callback) )
            $raw_posts = array_map($callback, $raw_posts);

        foreach ($raw_posts as $post){

            if( $post->getID() )
                $posts[$post->getID()] = $post;
            else
                $posts[] = $post;
        }

        if( isset($args['output']) && $args['output'] == 'array' )
            $posts = array_values( $posts );

        if( !isset($this->data[$key]) || (isset($args['override']) && $args['override']) )
            $this->data[$key] = $posts;
        else
            $this->data[$key] = array_merge($this->data[$key], $posts);

        return $this->data[$key];
    }


    /**
     * Retrieve paginated link for archive post pages.
     * @param array $args
     * @return array
     */
    public function addPagination($args=[], $key='pagination')
    {
        $this->data[$key] = $this->paginationService->build($args);

        return  $this->data[$key];
    }


    /**
     * QueryHelper terms
     * @param array $args see https://developer.wordpress.org/reference/classes/wp_term_query/__construct/,
     * added output=array|object, group=bool and sort=bool
     * @param string $key
     * @param false|callable $callback
     * @return Term[]
     */
    public function addTerms($args=[], $key='terms', $callback=false){

        $raw_terms = $this->termRepository->findBy($args);
        $terms = [];

        if( isset($args['taxonomy'], $args['group']) && is_array($args['taxonomy']) && $args['group']) {

            foreach ($raw_terms as $term)
                $terms[$term->taxonomy][$term->getID()] = is_wp_error($term) ? false : $term;

            $ordered_terms =[];

            foreach ($args['taxonomy'] as $taxonomy){

                if( isset($terms[$taxonomy]) )
                    $ordered_terms[$taxonomy] = $terms[$taxonomy];
            }

            $terms = $ordered_terms;
        }
        else {

            foreach ($raw_terms as $term)
                $terms[$term->getID()] = is_wp_error($term) ? false : $term;
        }

        if( $callback && is_callable($callback) )
            $terms = array_map($callback, $terms);

        if( isset($args['output']) && $args['output'] == 'array' )
            $terms = array_values( $terms );

        if( !isset($this->data[$key]) )
            $this->data[$key] = $terms;
        else
            $this->data[$key] = array_merge($this->data[$key], $terms);

        return $this->data[$key];
    }


    /**
     * Add breadcrumb entries
     * @param array $data
     * @param bool $add_current
     * @param bool $add_home
     * @return object[]
     *
     */
    public function addBreadcrumb($data=[], $add_current=true, $add_home=true)
    {
        $this->data['breadcrumb'] = $this->breadcrumbService->build(['add_current'=>$add_current, 'add_home'=>$add_home, 'data'=>$data]);

        if( !empty($data) ){

            foreach (($this->data['menu']??[]) as $menu){

                foreach(($menu['items']??[]) as &$item){

                    foreach ($data as $entry){

                        if( $item['link'] == $entry['link'] && strpos('current-menu-ancestor', $item['class']) === false )
                            $item['class'].= ' current-menu-ancestor';
                    }
                }
            }
        }

        return $this->data['breadcrumb'];
    }
	/**
	 * Add generic entry
	 *
	 * @see Post
	 * @internal param null $id
	 * @param $key
	 * @param $value
	 */
	public function add($key, $value='')
	{
		if( is_array($key) )
		{
			$this->data = array_merge($this->data, $key);
		}
		else
		{
			$this->data[$key] = $value;
		}
	}


	/**
	 * Remove generic entry
	 *
	 * @param $key
	 */
	public function remove($key)
	{
		if( isset($this->data[$key]) )
			unset($this->data[$key]);
	}


    /**
     * Get entry using dot notation
     *
     * @param $key
     * @param bool $fallback
     * @return array|bool
     * @internal param null $id
     * @see Post
     */
	public function get($key, $fallback=false)
	{
		$keys = explode('.', $key);
		$data = $this->data;

		foreach ($keys as $key)
		{
			if( isset(((array)$data)[$key]) )
				$data = is_object($data)?$data->$key:$data[$key];
			else
				return $fallback;
		}

		return $data;
	}


	/**
	 * Output Json Formatted Context
	 */
	public function debug()
	{
		header('Content-Type: application/json');

		echo json_encode($this->data, JSON_PRETTY_PRINT);

		exit;
	}


	/**
	 * Return Context as Array
	 * @return array
	 */
	public function toArray()
	{
		$this->add('environment', $_SERVER['APP_ENV']);

		if( isset($_GET['debug']) && $_GET['debug'] == 'context' && $_SERVER['APP_ENV'] == 'dev' )
			$this->debug();

		return is_array($this->data) ? $this->data : [];
	}
}
