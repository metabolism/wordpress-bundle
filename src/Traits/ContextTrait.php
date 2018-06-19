<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Metabolism\WordpressBundle\Traits;

use Metabolism\WordpressBundle\Entity\Comment;
use Metabolism\WordpressBundle\Helper\ACFHelper;
use Metabolism\WordpressBundle\Plugin\TermsPlugin;
use Metabolism\WordpressBundle\Provider\WooCommerceProvider;

use Metabolism\WordpressBundle\Entity\Post,
	Metabolism\WordpressBundle\Entity\Query,
	Metabolism\WordpressBundle\Entity\Term,
	Metabolism\WordpressBundle\Entity\Menu;


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
	public $has_templates, $config;


	/**
	 * Context constructor.
	 */
	public function __construct()
	{
		global $_config;
		$this->config = $_config;

		$this->has_templates = in_array('templates', $_config->get('support', []));

		$this->addSite();
		$this->addMenu();
		$this->addOptions();
		$this->addCurrent();
	}


	protected function addOptions()
	{
		$this->data['options'] = $this->getFields('options');
	}


	protected function getFields($id)
	{
		$fields = new ACFHelper($id);
		return $fields->get();
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
		global $wp_query;

		$blog_language = get_bloginfo('language');
		$language = explode('-', $blog_language);
		$languages = [];

		if( defined('ICL_LANGUAGE_CODE') )
		{
			$languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=desc' );
		}
		elseif(  defined('MSLS_PLUGIN_VERSION') and is_multisite() )
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
			'debug'            => WP_DEBUG,
			'environment'      => $this->config->get('environment', 'production'),
			'locale'           => count($language) ? $language[0] : 'en',
			'language'         => $blog_language,
			'languages'        => $languages,
			'is_admin'         => current_user_can('manage_options'),
			'home_url'         => home_url(),
			'search_url'       => get_search_link(),
			'maintenance_mode' => wp_maintenance_mode()
		];

		if( is_multisite() )
			$this->data['network_home_url'] = trim(network_home_url(), '/');

		$this->data = array_merge($this->data, [
			'maintenance_mode' => wp_maintenance_mode(),
			'tagline' => get_bloginfo('description'),
			'posts_per_page' => get_option( 'posts_per_page' )
		]);

		if( $this->has_templates && (is_single() || is_archive()) )
		{
			$wp_title = wp_title(' ', false);

			$this->data = array_merge($this->data, [
				'body_class' => $blog_language . ' ' . implode(' ', get_body_class()),
				'page_title' => empty($wp_title) ? get_the_title( get_option('page_on_front') ) : $wp_title,
				'system' => [
					'head'   => $this->getOutput('wp_head'),
					'footer' => $this->getOutput('wp_footer')
				]
			]);

			if (class_exists('WooCommerce'))
			{
				$wcProvider = WooCommerceProvider::getInstance();
				$wcProvider->globalContext($this->data);
			}
		}
	}


	public function addMenu()
	{
		$menus = get_registered_nav_menus();
		$this->data['menu'] = [];

		foreach ( $menus as $location => $description )
		{
			$menu = new Menu($location);

			if( $menu->id )
				$this->data['menu'][$location] = new Menu($location);
		}
	}


	public function addCurrent()
	{
		if( (is_single() or is_page()) and !is_attachment() )
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
	 * Add post entry to context
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

		if( $id )
		{
			$post = new Post($id);

			if( $callback and is_callable($callback) )
				call_user_func($callback, $post);

			$this->data[$key] = $post;

			return $this->data[$key];
		}

		return false;
	}


	/**
	 * Add term entry to context
	 *
	 * @see Post
	 * @param null $id
	 * @param string $key
	 * @return mixed
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
			$term = new Term($id);

			if( $callback and is_callable($callback) )
				call_user_func($callback, $term);

			$this->data[$key] = $term;

			return $this->data[$key];
		}

		return false;
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
	 * @param array $args see https://codex.wordpress.org/Class_Reference/WP_Query#Parameters
	 * @param string $key the key name to store data
	 * @param bool $found_posts include found posts value
	 * @param bool $callback execute a function for each result via array_map
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
	 * Retrieve paginated link for archive post pages.
	 * @see paginate_links
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
	 * Add post entry to context with current Post instance
	 * @see Post
	 */
	public function addTerms($args=[], $key='terms', $sort=true)
	{
		$raw_terms = Query::get_terms($args);
		$terms = [];

		if( isset($args['taxonomy'], $args['group']) && is_array($args['taxonomy']) && $args['group']) {

			foreach ($raw_terms as $term)
				$terms[$term->taxonomy][$term->term_id] = $term;

			if( $sort ){

				foreach ($terms as &$term_group)
					$term_group = TermsPlugin::sortHierarchically( $term_group );
			}
		}
		else
		{
			if( $sort )
				$raw_terms = TermsPlugin::sortHierarchically( $raw_terms );

			foreach ($raw_terms as $term)
				$terms[$term->term_id] = $term;
		}

		$this->data[$key] = $terms;

		return $this->data[$key];
	}


	/**
	 * Add breadcrumd entries
	 *
	 */
	public function addBreadcrumb($data=[])
	{
		$breadcrumb = [];

		$breadcrumb[] = ['title' => __('Home'), 'link' => get_home_url()];

		$breadcrumb = array_merge($breadcrumb, $data);

		if( (is_single() or is_page()) and !is_attachment() )
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

		$this->data['breadcrumb'] = $breadcrumb;
		
		return $this->data['breadcrumb'];
	}


	/**
	 * Add comments entries
	 * See : https://codex.wordpress.org/get_comments
	 *
	 */
	public function addComments($args=[], $key='post')
	{
		$args['fields'] = 'ids';

		if( !isset($args['include_unapproved']))
			$args['include_unapproved'] = false;

		if( !isset($args['number']))
			$args['number'] = 5;


		$comments = get_comments($args);

		foreach ($comments as &$comment)
		{
			$comment = new Comment($comment);
		}

		if( isset($this->data[$key]))
		{
			if( is_array($this->data[$key]) )
				$this->data[$key]['comments'] = $comments;
			else
				$this->data[$key]->comments = $comments;

		}
		else
			$this->data[$key] = $comments;

		return $comments;
	}
}
