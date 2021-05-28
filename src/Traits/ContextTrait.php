<?php

namespace Metabolism\WordpressBundle\Traits;

use lloc\Msls\MslsOptions;

use Metabolism\WordpressBundle\Entity\User;
use Metabolism\WordpressBundle\Factory\PostFactory,
	Metabolism\WordpressBundle\Factory\TaxonomyFactory;
use Metabolism\WordpressBundle\Helper\ACFHelper,
	Metabolism\WordpressBundle\Helper\QueryHelper;
use Metabolism\WordpressBundle\Plugin\ConfigPlugin;
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
		$this->addContent();
		$this->addCurrentUser();
	}


	/**
	 * load ACFHelper options
	 * @return void
	 */
	protected function addOptions()
	{
		$this->data['options'] = ACFHelper::get('options');
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
	 * Return function echo
	 * @param MslsOptions $mslsOptions
	 * @param WP_Site $site
	 * @param string $locale
	 * @return string
	 */
	private function getAlternativeLink($mslsOptions, $site, $locale)
	{
		switch_to_blog($site->blog_id);

		if ( MslsOptions::class != get_class( $mslsOptions ) && ( is_null( $mslsOptions ) || ! $mslsOptions->has_value( $locale ) ) ) {
			restore_current_blog();
			return false;
		}

		$alternate = $mslsOptions->get_permalink( $locale );

		restore_current_blog();

		return $alternate;
	}


	/**
	 * Get multisite multilangue data
	 * @param object $queried_object
	 * @return array
	 */
	protected function getLanguagesData($queried_object){

		$languages = [];

		if( defined('ICL_LANGUAGE_CODE') )
		{
			$languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=desc' );
		}
		elseif( defined('MSLS_PLUGIN_VERSION') && is_multisite() )
		{
			$sites = get_sites(['public'=>1]);
			$current_blog_id = get_current_blog_id();

			if( !function_exists('format_code_lang') )
				require_once(ABSPATH . 'wp-admin/includes/ms.php');

			$mslsOptions = MslsOptions::create();

			foreach($sites as $site)
			{
				$locale    = get_blog_option($site->blog_id, 'WPLANG');
				$locale    = empty($locale)? 'en_US' : $locale;
				$lang      = explode('_', $locale)[0];
				
				$alternate = $current_blog_id != $site->blog_id ? $this->getAlternativeLink($mslsOptions, $site, $locale) : false;

				$languages[] = [
					'id' => $site->blog_id,
					'active' => $current_blog_id==$site->blog_id,
					'name' => format_code_lang($lang),
					'home_url'      => get_home_url($site->blog_id, '/'),
					'language_code' => $lang,
					'url'           => $alternate
				];
			}
		}

		return $languages;
	}

	/**
	 * Add global data
	 */
	protected function addSite()
	{
		global $wp_query, $wp_rewrite;

		$blog_language = get_bloginfo('language');
		$queried_object = $wp_query->get_queried_object();

		$language  = explode('-', $blog_language);
		$paged     = get_query_var('paged', 1);

		$this->data = [
			'debug'              => WP_DEBUG,
			'environment'        => WP_ENV,
			'locale'             => count($language) ? $language[0] : 'en',
			'is_admin'           => current_user_can('manage_options'),
			'home_url'           => home_url('/'),
			'maintenance_mode'   => function_exists('wp_maintenance_mode') ? wp_maintenance_mode() : false,
			'bloginfo'           => [
				'description'   => get_bloginfo('description'),
				'name'          => get_bloginfo('name'),
				'charset'       => get_bloginfo('charset'),
				'language'      => $blog_language,
			],
			'posts_per_page'     => intval(get_option( 'posts_per_page' )),
			'paged'              => $paged ? $paged : 1
		];

		if( is_multisite() ){

			$languages = $this->getLanguagesData($queried_object);

			$this->data['network_home_url'] = trim(network_home_url(), '/');
			$this->data['languages'] = $languages;
		}

		if( !HEADLESS || URL_MAPPING )
		{
			$wp_title = trim(@wp_title(' ', false));
			$body_class = $queried_object ? implode(' ', get_body_class()) : '';

			$policy_page_id       = (int) get_option( 'wp_page_for_privacy_policy' );
			$privacy_policy_title = ( $policy_page_id ) ? get_the_title( $policy_page_id ) : '';

			$this->data = array_merge($this->data, [
				'search_url'           => get_search_link(),
				'privacy_policy_url'   => get_privacy_policy_url(),
				'privacy_policy_title' => $privacy_policy_title,
				'is_customize_preview' => is_customize_preview(),
				'is_front_page'        => is_front_page(),
				'is_single'            => isset($queried_object->post_type)?$queried_object->post_type:false,
				'is_tax'               => isset($queried_object->taxonomy)?$queried_object->taxonomy:false,
				'is_archive'           => is_object($queried_object) && get_class($queried_object) == 'WP_Post_Type' ? $queried_object->name : false,
				'body_class'           => $blog_language . ' ' . $body_class,
				'wp_title'             => html_entity_decode(empty($wp_title) ? get_the_title( get_option('page_on_front') ) : $wp_title)
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

		$depth = $this->config->get('menu.depth',  true);

		foreach ( $menus as $location => $description )
		{
			$menu = new Menu($location);

			if( $menu->ID ){

				if( $depth ){

					$this->data['menu'][$location] = $menu;
				}
				else{

					foreach ($menu->items as &$item )
						$item->location = $location;

					$this->data['menu'] = array_merge($this->data['menu'], $menu->items);
				}
			}
		}

		return $this->data['menu'];
	}


	/**
	 * Get queried object
	 * @return object|array
	 */
	public function getQueriedObject()
	{
		global $wp_query;
		return $wp_query->get_queried_object();
	}


	/**
	 * Get current post
	 * @return Post|bool
	 */
	public function getPost()
	{
		return $this->get('post');
	}


	/**
	 * Get current post
	 * @return Term|bool
	 */
	public function getTerm()
	{
		return $this->get('term');
	}


	/**
	 * Get current posts
	 * @return Post[]|bool
	 */
	public function getPosts()
	{
		return $this->get('posts');
	}


	/**
	 * Get menus
	 * @return Menu[]|bool
	 */
	public function getMenus()
	{
		return $this->get('menu');
	}


	/**
	 * Get options
	 * @return array
	 */
	public function getOptions()
	{
		return $this->get('options');
	}


	/**
	 * Get current user
	 * @return User|bool
	 */
	public function getCurrentUser()
	{
		return $this->get('current_user');
	}


	/**
	 * Add list of all wordpress post, page and custom post
	 * @param array $args see https://codex.wordpress.org/Class_Reference/WP_Query#Parameters
	 * @param bool $title_meta
	 * @return array
	 */
	public function addSitemap($args=[], $title_meta=false)
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
	protected function addContent()
	{
		if( (is_single() || is_page()) && !is_attachment() )
		{
			return $this->addPost();
		}
		elseif( is_archive() )
		{
			return ['term'=>$this->addTerm(), 'posts'=>$this->addPosts()];
		}
		elseif( is_search() || is_home() )
		{
			return $this->addPosts();
		}

		return false;
	}



	/**
	 * Add connected user
	 * @return User|bool
	 */
	protected function addCurrentUser()
	{
		$current_user_id = get_current_user_id();
		$this->data['current_user'] = $current_user_id ? new User($current_user_id) : false;

		return $this->data['current_user'];
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
	 * Add term entry to context from id or object with field, value and taxonomy to perform a get_term_by
	 *
	 * @see Post
	 * @param int|object $id
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
			if( is_array($id) && isset($id['field'], $id['value'], $id['taxonomy']) ){
				$term = get_term_by($id['field'], $id['value'], $id['taxonomy']);
				if( $term )
					$id = $term->term_id;
			}

			$term = TaxonomyFactory::create($id);

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

		$wp_query = QueryHelper::wp_query($args);
		$raw_posts = $wp_query->posts;
		$posts = [];

		if( isset($args['found_posts']) && $args['found_posts']) {

			if( !isset($this->data['found_'.$key]) )
				$this->data['found_'.$key] = $wp_query->found_posts;
			else
				$this->data['found_'.$key] += $wp_query->found_posts;
		}

		if( $callback && is_callable($callback) )
			$raw_posts = array_map($callback, $raw_posts);

		foreach ($raw_posts as $post){

			if( isset($post->ID) )
				$posts[$post->ID] = $post;
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

		if ( $total < 2 ){
			$this->data['pagination'] = false;
			return false;
		}

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

			$pagination['prev'] = ['link' => esc_url(apply_filters('paginate_links', $link)), 'text' => $args['prev_text']];
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

					$pagination['pages'][] = ['current'=>false, 'link'=> esc_url( apply_filters( 'paginate_links', $link ) ), 'text'=> $args['before_page_number'] . number_format_i18n( $n ) . $args['after_page_number']];
					$dots = true;
				elseif ( $dots && ! $args['show_all'] ) :
					$pagination['pages'][] = ['current'=>false, 'link'=>false, 'text'=> __( '&hellip;' ) ];
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
	 * QueryHelper terms
	 * @param array $args see https://developer.wordpress.org/reference/classes/wp_term_query/__construct/,
	 * added output=array|object, group=bool and sort=bool
	 * @param string $key
	 * @param false|callable $callback
	 * @return Term[]
	 */
	public function addTerms($args=[], $key='terms', $callback=false)
	{
		$raw_terms = QueryHelper::get_terms($args);
		$terms = [];

		if( isset($args['taxonomy'], $args['group']) && is_array($args['taxonomy']) && $args['group']) {

			foreach ($raw_terms as $term)
				$terms[$term->taxonomy][$term->term_id] = is_wp_error($term) ? false : $term;

			if( !isset($args['child_of']) && (!isset($args['sort']) || $args['sort']) ){

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
			if( !isset($args['child_of']) && (!isset($args['sort']) || $args['sort'])  )
				$raw_terms = TermsPlugin::sortHierarchically( $raw_terms );

			foreach ($raw_terms as $term)
				$terms[$term->ID] = is_wp_error($term) ? false : $term;
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
}
