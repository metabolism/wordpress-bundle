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


	/**
	 * Redirect to admin
	 */
	public function redirect()
	{
		if( rtrim($_SERVER['REQUEST_URI'], '/') == BASE_PATH.WP_SUBFOLDER ){

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
			return;

		$post_type = get_query_var('post_type');

		if ( $query->is_archive and $post_type )
		{
			if( $ppp = $this->config->get('post_types.'.$post_type.'.posts_per_page') )
				$query->set( 'posts_per_page', $ppp );
		}

		if ( $query->is_tax and !$post_type )
		{
			global $wp_taxonomies;

			$taxo = get_queried_object();
			$post_type = ( isset($taxo->taxonomy, $wp_taxonomies[$taxo->taxonomy] ) ) ? $wp_taxonomies[$taxo->taxonomy]->object_type : array();

			$query->set('post_type', $post_type);
			$query->query['post_type'] = $post_type;
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

		add_filter('timber/post/get_preview/read_more_link', '__return_null' );
		add_filter('wp_calculate_image_srcset_meta', '__return_null');
	}


	/**
	 * Create Menu instances from configs
	 * @see Menu
	 */
	public function postsRequest($input)
	{
		if( $this->config->get('debug.show_query'))
			var_dump($input);

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

		add_action( 'pre_get_posts', [$this, 'preGetPosts'] );
	}
}
