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
		if( defined('DOING_CRON') && DOING_CRON )
			return;

		$path = rtrim($_SERVER['REQUEST_URI'], '/');

		if( !empty($path) && strpos($path, WP_FOLDER) !== false && 'POST' !== $_SERVER['REQUEST_METHOD'] ){

			wp_redirect(is_user_logged_in() ? admin_url('index.php') : wp_login_url());
			exit;
		}
	}


	/**
	 * Init placeholder
	 */
	public function init(){}


	/**
	 * Add custom post type for taxonomy archive page
	 * @param \WP_Query $query
	 * @return mixed
	 */
	public function preGetPosts( $query )
	{
		if( !$query->is_main_query() || is_admin() )
			return $query;

		global $wp_query;

		$object = $wp_query->get_queried_object();

		if ( $query->is_archive && is_object($object) )
		{
			if( get_class($object) == 'WP_Post_Type' ){

				if( $ppp = $this->config->get('post_type.'.$object->name.'.posts_per_page', false) )
					$query->set( 'posts_per_page', $ppp );

				if( $orderby = $this->config->get('post_type.'.$object->name.'.orderby', false) )
					$query->set( 'orderby', $orderby );

				if( $order = $this->config->get('post_type.'.$object->name.'.order', false) )
					$query->set( 'order', $order );
			}
			elseif( get_class($object) == 'WP_Term' ){

				if( $ppp = $this->config->get('taxonomy.'.$object->taxonomy.'.posts_per_page', false) )
					$query->set( 'posts_per_page', $ppp );

				if( $orderby = $this->config->get('taxonomy.'.$object->name.'.orderby', false) )
					$query->set( 'orderby', $orderby );

				if( $order = $this->config->get('taxonomy.'.$object->name.'.order', false) )
					$query->set( 'order', $order );
			}
		}

		if ( $query->is_tax && !get_query_var('post_type') )
		{
			global $wp_taxonomies;

			$post_type = ( isset($object->taxonomy, $wp_taxonomies[$object->taxonomy] ) ) ? $wp_taxonomies[$object->taxonomy]->object_type :[];

			$query->set('post_type', $post_type);
			$query->query['post_type'] = $post_type;
		}

		if( $query->is_search ) {

			if( $ppp = $this->config->get('search.posts_per_page', false) )
				$query->set( 'posts_per_page', $ppp );
		}

		// opti
		if( $post_type = $query->get('post_type') ){

            if( is_array($post_type) && count($post_type) == 1 ){

                $query->set('post_type', $post_type[0]);
                $query->query['post_type'] = $post_type[0];
            }
        }

		return $query;
	}


	/**
	 * Load App configuration
	 */
	private function loadConfig()
	{
		global $_config;
		$this->config = $_config;
	}


	public function __construct()
	{
		if( defined('WP_INSTALLING') && WP_INSTALLING )
			return;

		$this->loadConfig();


		add_action( 'init', [$this, 'init']);
		add_action( 'init', [$this, 'redirect']);
		add_action( 'init', '_wp_admin_bar_init', 0 );

		add_action( 'pre_get_posts', [$this, 'preGetPosts'] );
	}
}
