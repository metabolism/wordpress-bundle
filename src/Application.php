<?php

namespace Metabolism\WordpressLoader;

use Metabolism\WordpressLoader\Traits\SingletonTrait;

use Metabolism\WordpressLoader\Helper\ContextHelper as Context;

use Metabolism\WordpressLoader\Model\RouterModel as Router,
	Metabolism\WordpressLoader\Model\TermsModel as Terms;

use	Metabolism\WordpressLoader\Plugin\MediaPlugin,
	Metabolism\WordpressLoader\Plugin\SecurityPlugin,
	Metabolism\WordpressLoader\Plugin\MaintenancePlugin,
	Metabolism\WordpressLoader\Plugin\ConfigPlugin,
	Metabolism\WordpressLoader\Plugin\NoticePlugin,
	Metabolism\WordpressLoader\Plugin\BackupPlugin,
    Metabolism\WordpressLoader\Plugin\TemplatePlugin,
    Metabolism\WordpressLoader\Plugin\ThumbnailPlugin;

/**
 * Class Metabolism\WordpressLoader Framework
 */
class Application {

	use SingletonTrait;


    /**
     * @var string plugin domain name for translations
     */
	public static $acf_folder, $languages_folder;

    public static $domain_name = 'default';
    public static $bo_domain_name = 'bo_default';

    protected $router, $class_loader, $prevent_recurssion, $context;


    /**
     * Get archive id
     */
	protected function getSlug($id, $type){

		if( $type == 'archive' )
			return $this->config->get('post_types.'.$id.'.has_archive', $id.'s');

		if( $type == 'post' )
			return $this->config->get('post_types.'.$id.'.rewrite.slug', $id);
	}


    /**
     * Application Constructor
     */
    public function setup()
    {
    	if( defined('WP_INSTALLING') and WP_INSTALLING )
		    return;

        $this->definePaths();
        $this->loadConfig();

	    $this->registerPlugins();
	    $this->registerFilters();

        // Global init action
        add_action( 'init', function()
        {
	        $this->registerActions();

	        if( is_admin() )
	        {
		        $this->setTheme();
	        }
	        else
	        {
		        $this->router = new Router();
		        $this->router->setLocale(get_locale());

		        $this->registerRoutes();
	        }
        });

        // When viewing admin
        if( is_admin() )
        {
	        add_action( 'admin_init', [$this, 'updateEditorRole'] );

	        // Remove image sizes for thumbnails
            add_filter( 'intermediate_image_sizes_advanced', [$this, 'intermediateImageSizesAdvanced'] );
	        add_filter( 'wp_terms_checklist_args', [Terms::getInstance(), 'wp_terms_checklist_args'] );

            // Removes or add pages
	        add_action( 'admin_footer', [$this, 'adminFooter'] );
        }
        else
        {
            add_action( 'after_setup_theme', [$this, 'loadThemeTextdomain']);
	        add_action( 'pre_get_posts', [$this, 'preGetPosts'] );
	        add_action( 'wp_loaded', [$this, 'init']);
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
     * Unset thumbnail image
     */
    public function intermediateImageSizesAdvanced($sizes)
    {
        unset($sizes['medium'], $sizes['medium_large'], $sizes['large']);
        return $sizes;
    }


    /**
     * Define rocket theme as default theme.
     */
    public function setTheme()
    {
        $current_theme = wp_get_theme();

        if ($current_theme->get_stylesheet() != 'rocket')
            switch_theme('rocket');
    }


    /**
     * Clean WP Head
     */
    public function loadThemeTextdomain()
    {
    	if( is_dir($this::$languages_folder) )
		    load_theme_textdomain( $this::$domain_name, $this::$languages_folder );
    }


    protected function registerRoutes() {}

	protected function registerActions() {}

	public function adminFooter() {}
	

	/**
     * Register wp path
     */
    private function definePaths()
    {
        $this->paths = $this->getPaths();
        $this->paths['wp'] = CMS_URI;
    }


	public function updateEditorRole()
    {
	    $role_object = get_role( 'editor' );

	    if( !$role_object->has_cap('edit_theme_options') )
		    $role_object->add_cap( 'edit_theme_options' );
    }

    /**
     * Instantiate plugin at the right moment
     */
    public function registerPlugins()
    {
	    new ConfigPlugin($this->config);
	    new TemplatePlugin($this->config);
	    new MediaPlugin($this->config);
	    new MaintenancePlugin($this->config);
	    new SecurityPlugin($this->config);
	    new NoticePlugin($this->config);
	    new BackupPlugin($this->config);
	    new ThumbnailPlugin($this->config);
    }


    /**
     * Allows user to add specific process on Wordpress functions
     */
    public function registerFilters()
    {
	    add_filter('posts_request', [$this, 'postsRequest'] );

	    add_filter('woocommerce_template_path', function($array){ return '../../../WoocommerceBundle/'; });
	    add_filter('woocommerce_enqueue_styles', '__return_empty_array' );

        add_filter('acf/settings/save_json', function(){ return $this::$acf_folder; });
        add_filter('acf/settings/load_json', function(){ return [$this::$acf_folder]; });

	    add_filter('timber/post/get_preview/read_more_link', '__return_null' );
        add_filter('wp_calculate_image_srcset_meta', '__return_null');

	    add_filter( "site_option_siteurl", function($value){
	    	return str_replace('/ajax.php/', '/', $value);
	    });

        // Handle /edition in url
	    add_filter('option_siteurl', [$this, 'optionSiteURL'] );
	    add_filter('network_site_url', [$this, 'networkSiteURL'] );
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
     * Add edition folder to option url
     */
    public function networkSiteURL($url)
    {
	    if( strpos($url,'/edition') === false )
		    return str_replace('/wp-admin', '/edition/wp-admin', $url);
	    else
		    return $url;
    }


    /**
     * Add edition folder to option url
     */
    public function optionSiteURL($url)
    {
        return strpos($url, 'edition') === false ? $url.'/edition' : $url;
    }


    /**
     * Load App configuration
     */
    private function loadConfig()
    {
        $this->config = $this->getConfig('wordpress');

        self::$domain_name      = $this->config->get('domain_name', 'customer');
        self::$bo_domain_name   = 'bo_'.$this->config->get('domain_name', 'customer');
	    self::$acf_folder       = WP_CONTENT_DIR . '/acf-json';
	    self::$languages_folder = WP_CONTENT_DIR . '/languages';
    }


    /**
     * Init handler
     * @see Menu
     */
    public function init()
    {
	    $this->context = new Context();
    }


    /**
     * Define route manager
     * @param $template
     * @param bool $context
     * @return array
     */
    protected function page($template, $context=false)
    {
        return [$template, $context];
    }


	/**
	 * Return json data / Silex compatibility
	 * @param $data
	 * @return bool
	 */
    protected function json($data, $status_code = null)
    {
        wp_send_json($data, $status_code);

        return true;
    }


    /**
     * Register route
     * @param $pattern
     * @param $controller
     * @return Route
     */
    protected function route($pattern, $controller)
    {
        return $this->router->add($pattern, $controller);
    }


	/**
	 * Register route
	 * @param $id
	 * @param $controller
	 * @param bool $no_private
	 */
    protected function action($id, $controller, $no_private=true)
    {
    	if( class_exists( 'WooCommerce' ) )
	    {
		    add_action( 'woocommerce_api_'.$id, $controller );
	    }
	    else
	    {
		    add_action( 'wp_ajax_'.$id, $controller );

		    if( $no_private )
			    add_action( 'wp_ajax_nopriv_'.$id, $controller );
	    }
    }


    /**
     * Define route manager
     * @return bool|mixed
     */
    public function solve()
    {
        return $this->router->solve();
    }


	/**
	 * Define route manager
	 * @param int $code
	 * @return bool|mixed
	 */
    public function getErrorPage($code=404)
    {
        return $this->router->error($code);
    }


    public function __construct($autoloader=false)
    {
        $this->class_loader = $autoloader;
        $this->context = [];

        if( !defined('WPINC') )
            include CMS_URI.'/wp-blog-header.php';
    }
}
