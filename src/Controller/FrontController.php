<?php

namespace Metabolism\WordpressBundle\Controller;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class FrontController {

	/**
	 * @var string plugin domain name for translations
	 */
	public static $languages_folder;

	public static $domain_name = 'default';

	private $config;

	/**
	 * Redirect to admin
	 */
	public function redirect()
	{
		if( rtrim($_SERVER['REQUEST_URI'], '/') == WP_FOLDER ){

			wp_redirect(is_user_logged_in() ? admin_url() : wp_login_url());

			echo "redirect...";

			exit;
		}
	}


	/**
	 * Add custom post type for taxonomy archive page
	 */
	public function preGetPosts( $query )
	{
		if( !$query->is_main_query() || is_admin() )
			return $query;

		global $wp_query;

		$object = $wp_query->get_queried_object();

		if ( $query->is_archive )
		{
			if( get_class($object) == 'WP_Post_Type' && $ppp = $this->config->get('post_type.'.$object->name.'.posts_per_page') )
				$query->set( 'posts_per_page', $ppp );
			elseif( get_class($object) == 'WP_Term' && $ppp = $this->config->get('taxonomy.'.$object->taxonomy.'.posts_per_page') )
				$query->set( 'posts_per_page', $ppp );
		}

		if ( $query->is_tax and !get_query_var('post_type') )
		{
			global $wp_taxonomies;

			$post_type = ( isset($object->taxonomy, $wp_taxonomies[$object->taxonomy] ) ) ? $wp_taxonomies[$object->taxonomy]->object_type :[];

			$query->set('post_type', $post_type);
			$query->query['post_type'] = $post_type;
		}

		if( $query->is_search ) {

			if( $ppp = $this->config->get('search.posts_per_page') )
				$query->set( 'posts_per_page', $ppp );
		}

		return $query;
	}


	/**
	 * Allows user to add specific process on Wordpress functions
	 */
	public function registerFilters()
	{
		add_filter('posts_request', [$this, 'postsRequest'] );

		add_filter('woocommerce_template_path', function($array){ return '../../../WoocommerceBundle/'; });
		add_filter('woocommerce_enqueue_styles', '__return_empty_array' );

		add_filter('wp_calculate_image_srcset_meta', '__return_null');
	}


	/**
	 * Display sql requests
	 */
	public function postsRequest($input)
	{
		if( isset($_REQUEST['debug']) && $_REQUEST['debug'] == 'query' && $_SERVER['APP_ENV'] == 'dev' ){

			header('Content-Type: application/json');
			echo json_encode($input);
			exit(0);
		}

		return $input;
	}


	/**
	 * Load App configuration
	 */
	private function loadConfig()
	{
		global $_config;

		$this->config = $_config;

		self::$domain_name      = $this->config->get('domain_name', 'app');
		self::$languages_folder = WP_CONTENT_DIR . '/languages';
	}


	public function __construct()
	{
		if( defined('WP_INSTALLING') and WP_INSTALLING )
			return;

		$this->loadConfig();
		$this->registerFilters();

		add_action( 'init', [$this, 'redirect']);
		add_action( 'init', '_wp_admin_bar_init', 0 );

		add_action( 'pre_get_posts', [$this, 'preGetPosts'] );
	}
}
