<?php

namespace Metabolism\WordpressBundle\Traits;

use Metabolism\WordpressBundle\Factory\PostFactory,
	Metabolism\WordpressBundle\Factory\TaxonomyFactory;
use Metabolism\WordpressBundle\Helper\ACF,
	Metabolism\WordpressBundle\Helper\Query;
use Metabolism\WordpressBundle\Plugin\TermsPlugin;

use Metabolism\WordpressBundle\Entity\Post,
	Metabolism\WordpressBundle\Entity\Term,
	Metabolism\WordpressBundle\Entity\Menu,
	Metabolism\WordpressBundle\Entity\Comment;


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
	public $config;


	/**
	 * Context constructor.
	 */
	public function __construct()
	{
		global $_config;
		$this->config = $_config;

		$this->addSite();
		$this->addMenus();
		$this->addOptions();
		$this->addCurrent();
	}


	/**
	 * load ACF options
	 * @return void
	 */
	protected function addOptions()
	{
		$this->data['options'] = $this->getFields('options');
	}


	/**
	 * Get ACF Fields wrapper
	 * @param $id
	 * @return object|bool
	 */
	protected function getFields($id)
	{
		$fields = new ACF($id);
		return $fields->get();
	}


	/**
	 * Get current post
	 * @return Post|bool
	 */
	protected function getPost()
	{
		return $this->get('post');
	}


	/**
	 * Get current posts
	 * @return Post[]|bool
	 */
	protected function getPosts()
	{
		return $this->get('posts');
	}


	/**
	 * Return function echo
	 * @param $function
	 * @param array $args
	 * @return string
	 */
	protected function getOutput($function, $args=[])
	{
		ob_start();
		call_user_func_array($function, $args);
		$data = ob_get_contents();
		ob_end_clean();

		return $data;
	}


	/**
	 * Add global data
	 */
	protected function addSite()
	{
		global $wp_query, $wp_rewrite;

		$blog_language = get_bloginfo('language');
		$post_id = $wp_query->get_queried_object_id();

		$language = explode('-', $blog_language);
		$languages = [];

		if( defined('ICL_LANGUAGE_CODE') )
		{
			$languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=desc' );
		}
		elseif(  defined('MSLS_PLUGIN_VERSION') && is_multisite() )
		{
			$sites = get_sites(['public'=>1]);
			$current_blog_id = get_current_blog_id();

			if( !function_exists('format_code_lang') )
				require_once(ABSPATH . 'wp-admin/includes/ms.php');

			foreach($sites as $site)
			{
				$lang = get_blog_option($site->blog_id, 'WPLANG');
				$lang = empty($lang)?'en':(explode('_', $lang)[0]);
				$languages[] = [
					'id' => $site->blog_id,
					'active' => $current_blog_id==$site->blog_id,
					'name' => format_code_lang($lang),
					'url' => get_home_url($site->blog_id, '/'),
					'language_code' => $lang
				];
			}
		}

		$this->data = [
			'debug'              => WP_DEBUG,
			'environment'        => $this->config->get('environment', 'production'),
			'locale'             => count($language) ? $language[0] : 'en',
			'language'           => $blog_language,
			'languages'          => $languages,
			'is_admin'           => current_user_can('manage_options'),
			'home_url'           => home_url('/'),
			'maintenance_mode'   => wp_maintenance_mode(),
			'tagline'            => get_bloginfo('description'),
			'site_title'         => get_bloginfo('name'),
			'posts_per_page'     => get_option( 'posts_per_page' )
		];

		if( is_multisite() )
			$this->data['network_home_url'] = trim(network_home_url(), '/');

		if( WP_FRONT && (!is_singular() || $post_id) )
		{
			$wp_title = trim(wp_title(' ', false));

			$this->data = array_merge($this->data, [
				'search_url'         => get_search_link(),
				'privacy_policy_url' => get_privacy_policy_url(),
				'is_front_page'      => is_front_page(),
				'body_class'         => $blog_language . ' ' . implode(' ', get_body_class()),
				'page_title'         => empty($wp_title) ? get_the_title( get_option('page_on_front') ) : $wp_title,
				'system' => [
					'head'   => $this->getOutput('wp_head'),
					'footer' => $this->getOutput('wp_footer')
				]
			]);
		}
	}


	/**
	 * Add wordpress defined menus
	 * @return Menu[]
	 *
	 */
	protected function addMenus()
	{
		$menus = get_registered_nav_menus();
		$this->data['menu'] = [];

		foreach ( $menus as $location => $description )
		{
			$menu = new Menu($location);

			if( $menu->ID )
				$this->data['menu'][$location] = new Menu($location);
		}

		return $this->data['menu'];
	}


	/**
	 * Add list of all wordpress post, page and custom post
	 * @param array $args see https://codex.wordpress.org/Class_Reference/WP_Query#Parameters
	 * @param string $title_meta
	 * @return array
	 *
	 */
	protected function addSitemap($args=[], $title_meta=false)
	{
		$sitemap = [];

		$query = array_merge([
			'post_type' => 'any',
			'posts_per_page' => -1
		], $args);

		$query = new \WP_Query($query);

		if( isset($query->posts) && is_array($query->posts) )
		{
			foreach ($query->posts as $post)
			{
				$template = get_page_template_slug($post);
				$title = $title_meta ? get_post_meta($post->ID, $title_meta, true) : false;
				$sitemap[] = [
					'link'=> get_permalink($post),
					'template' => empty($template)?'default':$template,
					'name' => $post->post_name,
					'type' => $post->post_type,
					'modified' => $post->post_modified,
					'title' => $title?$title:strip_tags($post->post_title),
					'ID' => $post->ID
				];
			}
		}

		$this->data['sitemap'] = $sitemap;

		return $sitemap;
	}


	/**
	 * Get default wordpress data
	 * @return Post|array|bool
	 */
	protected function addCurrent()
	{
		if( (is_single() || is_page()) && !is_attachment() )
		{
			return $this->addPost();
		}
		elseif( is_archive() )
		{
			return ['term'=>$this->addTerm(), 'posts'=>$this->addPosts()];
		}
		elseif( is_search() )
		{
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
	public function addPost($id = null, $key='post', $callback=false)
	{
		if( is_null($id) )
			$id = get_the_ID();

		if( $id )
		{
			$post = PostFactory::create($id);

			if( $callback && is_callable($callback) )
				call_user_func($callback, $post);

			$this->data[$key] = $post;

			return $this->data[$key];
		}

		return false;
	}


	/**
	 * Add term entry to context from id
	 *
	 * @see Post
	 * @param null $id
	 * @param string $key
	 * @param callable|bool $callback
	 * @return Term|bool
	 */
	public function addTerm($id = null, $key='term', $callback=false)
	{
		if( is_null($id) )
		{
			global $wp_query;
			$cat_obj = $wp_query->get_queried_object();

			if( $cat_obj && isset($cat_obj->term_id))
				$id = $cat_obj->term_id;
		}

		if( $id )
		{
			$term = TaxonomyFactory::create($id);

			if( $callback && is_callable($callback) )
				call_user_func($callback, $term);

			$this->data[$key] = $term;

			return $this->data[$key];
		}

		return false;
	}


	/**
	 * Query posts
	 *
	 * @see Post
	 * @param array $args see https://codex.wordpress.org/Class_Reference/WP_Query#Parameters
	 * @param bool|string $key the key name to store data
	 * @param callable|bool $callback execute a function for each result via array_map
	 * @return Post[]
	 */
	public function addPosts($args=[], $key='posts', $callback=false){

		$wp_query = Query::wp_query($args);
		$raw_posts = $wp_query->posts;
		$posts = [];

		if( isset($args['found_posts']) && $args['found_posts']) {

			if( !isset($this->data['found_'.$key]) )
				$this->data['found_'.$key] = 0;
			else
			$this->data['found_'.$key] += $wp_query->found_posts;
		}

		if( $callback && is_callable($callback) )
			array_map($callback, $raw_posts);

		foreach ($raw_posts as $post)
			$posts[$post->ID] = $post;

		if( !isset($this->data[$key]) )
			$this->data[$key] = $posts;
		else
			$this->data[$key] = array_merge($this->data[$key], $posts);

		return $this->data[$key];
	}


	/**
	 * Retrieve paginated link for archive post pages.
	 * @return object|bool
	 */
	public function addPagination($args=[])
	{
		global $wp_query, $wp_rewrite;

		$pagenum_link = html_entity_decode( get_pagenum_link() );
		$url_parts    = explode( '?', $pagenum_link );

		$total   = isset( $wp_query->max_num_pages ) ? $wp_query->max_num_pages : 1;
		$current = get_query_var( 'paged' ) ? intval( get_query_var( 'paged' ) ) : 1;

		$pagenum_link = trailingslashit( $url_parts[0] ) . '%_%';

		$format  = $wp_rewrite->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
		$format .= $wp_rewrite->using_permalinks() ? user_trailingslashit( $wp_rewrite->pagination_base . '/%#%', 'paged' ) : '?paged=%#%';

		$defaults = array(
			'base'               => $pagenum_link,
			'format'             => $format,
			'total'              => $total,
			'current'            => $current,
			'show_all'           => false,
			'prev_text'          => __( 'Previous' ),
			'next_text'          => __( 'Next' ),
			'end_size'           => 1,
			'mid_size'           => 2,
			'add_args'           => array(),
			'add_fragment'       => '',
			'before_page_number' => '',
			'after_page_number'  => '',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( ! is_array( $args['add_args'] ) )
			$args['add_args'] = array();

		if ( isset( $url_parts[1] ) ) {

			$format = explode( '?', str_replace( '%_%', $args['format'], $args['base'] ) );
			$format_query = isset( $format[1] ) ? $format[1] : '';
			wp_parse_str( $format_query, $format_args );

			wp_parse_str( $url_parts[1], $url_query_args );

			foreach ( $format_args as $format_arg => $format_arg_value )
				unset( $url_query_args[ $format_arg ] );

			$args['add_args'] = array_merge( $args['add_args'], urlencode_deep( $url_query_args ) );
		}

		$total = (int) $args['total'];

		if ( $total < 2 )
			return false;

		$current  = (int) $args['current'];
		$end_size = (int) $args['end_size'];
		if ( $end_size < 1 )
			$end_size = 1;

		$mid_size = (int) $args['mid_size'];
		if ( $mid_size < 0 )
			$mid_size = 2;

		$add_args = $args['add_args'];
		$r = '';
		$pagination = [];
		$dots = false;

		if ( $current && 1 < $current ):
			$link = str_replace('%_%', 2 == $current ? '' : $args['format'], $args['base']);
			$link = str_replace('%#%', $current - 1, $link);
			if ($add_args)
				$link = add_query_arg($add_args, $link);
			$link .= $args['add_fragment'];

			$pagination['previous'] = ['link' => esc_url(apply_filters('paginate_links', $link)), 'text' => $args['prev_text']];
		endif;

		$pagination['pages'] = [];

		for ( $n = 1; $n <= $total; $n++ ) :
			if ( $n == $current ) :
				$pagination['pages'][] = ['current'=>true, 'text'=> $args['before_page_number'] . number_format_i18n( $n ) . $args['after_page_number']];
				$dots = true;
			else :
				if ( $args['show_all'] || ( $n <= $end_size || ( $current && $n >= $current - $mid_size && $n <= $current + $mid_size ) || $n > $total - $end_size ) ) :
					$link = str_replace( '%_%', 1 == $n ? '' : $args['format'], $args['base'] );
					$link = str_replace( '%#%', $n, $link );
					if ( $add_args )
						$link = add_query_arg( $add_args, $link );
					$link .= $args['add_fragment'];

					$pagination['pages'][] = ['link'=> esc_url( apply_filters( 'paginate_links', $link ) ), 'text'=> $args['before_page_number'] . number_format_i18n( $n ) . $args['after_page_number']];
					$dots = true;
				elseif ( $dots && ! $args['show_all'] ) :
					$pagination['pages'][] = ['text'=> __( '&hellip;' ) ];
					$dots = false;
				endif;
			endif;
		endfor;

		if ( $current && $current < $total ) :
			$link = str_replace( '%_%', $args['format'], $args['base'] );
			$link = str_replace( '%#%', $current + 1, $link );
			if ( $add_args )
				$link = add_query_arg( $add_args, $link );
			$link .= $args['add_fragment'];

			$pagination['next'] = ['link'=> esc_url( apply_filters( 'paginate_links', $link ) ), 'text'=> $args['next_text'] ];
		endif;

		$this->data['pagination'] = $pagination;

		return $this->data['pagination'];
	}


	/**
	 * Query terms
	 * @param array $args see https://developer.wordpress.org/reference/classes/wp_term_query/__construct/
	 * @param string $key
	 * @param false|callable $callback
	 * @return Term[]
	 */
	public function addTerms($args=[], $key='terms', $callback=false)
	{
		$raw_terms = Query::get_terms($args);
		$terms = [];

		if( isset($args['taxonomy'], $args['group']) && is_array($args['taxonomy']) && $args['group']) {

			foreach ($raw_terms as $term)
				$terms[$term->taxonomy][$term->term_id] = $term;

			if( !isset($args['sort']) || $args['sort'] ){

				foreach ($terms as &$term_group)
					$term_group = TermsPlugin::sortHierarchically( $term_group );
			}

			$ordered_terms =[];

			foreach ($args['taxonomy'] as $taxonomy){

				if( isset($terms[$taxonomy]) )
					$ordered_terms[$taxonomy] = $terms[$taxonomy];
			}

			$terms = $ordered_terms;
		}
		else
		{
			if( !isset($args['sort']) || $args['sort']  )
				$raw_terms = TermsPlugin::sortHierarchically( $raw_terms );

			foreach ($raw_terms as $term)
				$terms[$term->ID] = $term;
		}

		if( $callback && is_callable($callback) )
			array_map($callback, $terms);

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
		$breadcrumb = [];

		if( $add_home )
			$breadcrumb[] = ['title' => __('Home'), 'link' => home_url('/')];

		$breadcrumb = array_merge($breadcrumb, $data);

		if( $add_current ){

			if( (is_single() || is_page()) && !is_attachment() )
			{
				$post = $this->get('post');

				if( $post )
					$breadcrumb[] = ['title' => $post->title];
			}
			elseif( is_archive() )
			{
				$term = $this->get('term');

				if( $term )
					$breadcrumb[] = ['title' => $term->title];
			}
		}

		$this->data['breadcrumb'] = $breadcrumb;
		
		return $this->data['breadcrumb'];
	}


	/**
	 * Add comments entries
	 * @param array $args see https://codex.wordpress.org/get_comments
	 * @param string $key
	 * @return Comment[]
	 *
	 */
	public function addComments($args=[], $key='comments')
	{
		$args['fields'] = 'ids';

		if( !isset($args['status']))
			$args['status'] = 'approve';

		if( !isset($args['number']))
			$args['number'] = 5;

		$comments_id = get_comments($args);
		$comments = [];

		foreach ($comments_id as $comment_id)
		{
			$comments[$comment_id] = new Comment($comment_id);
		}

		// todo: check recursivity
		foreach ($comments as $comment)
		{
			if( $comment->parent )
			{
				$comments[$comment->parent]->replies[] = $comment;
				unset($comments[$comment->ID]);
			}
		}

		$comments_count = wp_count_comments(isset($args['post_id'])?$args['post_id']:0 );

		if( isset($this->data['post']))
		{
			if( is_array($this->data['post']) ){
				$this->data['post'][$key] = $comments;
				$this->data['post']['comments_count'] = $comments_count;
			}
			else{
				$this->data['post']->$key = $comments;
				$this->data['post']->comments_count = $comments_count;
		}
		}
		else{

			$this->data[$key] = $comments;
			$this->data['comments_count'] = $comments_count;
		}

		return $comments;
	}
}
