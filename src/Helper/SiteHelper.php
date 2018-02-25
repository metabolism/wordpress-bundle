<?php

namespace Metabolism\WordpressLoader\Helper;

use Metabolism\WordpressLoader\Controller\FrontController;

use Metabolism\WordpressLoader\Helper\Manifest;
use Metabolism\WordpressLoader\Traits\SingletonTrait,
	Metabolism\WordpressLoader\Provider\WooCommerceProvider;

use Timber\Timber,
    Timber\Site,
    Timber\Menu as TimberMenu;


class SiteHelper extends Site
{
    public $theme_name = 'rocket';
	private $app, $config;

    public function __construct()
    {
	    parent::__construct();

	    if (class_exists('Timber'))
		    Timber::$locations = [BASE_URI . '/src/FrontBundle/Views/', BASE_URI . '/vendor/metabolism/wordpress-loader/tools/views'];

        add_filter( 'timber_context', [$this, 'addToContext']);
        add_filter( 'get_twig', [$this, 'addToTwig']);
	    add_action( 'wp_head', [$this, 'headAction']);
	    add_action( 'wp_footer', [$this, 'footerAction']);

	    /** @var Application $app */
	    global $_config;
	    $this->config = $_config;
    }


    public function headAction()
    {
	    if( WP_DEBUG )
		    Timber::render( 'component/header.debug.twig', [
		    	'config'    => $this->config->export(),
			    'framework' => 'wordpress'
		    ]);
    }


    public function footerAction()
    {
	    if( WP_DEBUG )
		    Timber::render( 'component/footer.debug.twig', [
		    	'config'      =>$this->config->export(),
			    'environment' => $this->config->get('environment', 'production'),
			    'last_update' => strtotime(shell_exec('git log -1 --format=%cd')),
			    'base_url'    => get_option('home'),
			    'cookies'     => $_COOKIE,
			    'host'        => 'http://' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's':'') . $_SERVER['HTTP_HOST'],
			    'framework'   => 'wordpress'
	    ]);
    }


    public function addToContext($context)
    {
        $language = explode('-', get_bloginfo('language'));

        if( function_exists('wpml_get_active_languages_filter') )
            $languages = wpml_get_active_languages_filter('','skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str');
        else
            $languages = [];

        $context = array_merge($context, [
            'debug'            => WP_DEBUG,
            'environment'      => $this->config->get('environment', 'production'),
            'locale'           => count($language) ? $language[0] : 'en',
            'languages'        => $languages,
            'is_admin'         => current_user_can('manage_options'),
            'body_class'       => get_bloginfo('language') . ' ' . implode(' ', get_body_class()),
            'base_url'         => get_bloginfo('url'),
            'maintenance_mode' => wp_maintenance_mode()
        ]);

        $menus = get_registered_nav_menus();
        $context['menus'] = [];

        foreach ( $menus as $location => $description )
            $context['menus'][$location] = new TimberMenu($location);

        if (class_exists('WooCommerce'))
        {
            $wcProvider = WooCommerceProvider::getInstance();
            $wcProvider->globalContext($context);
        }

        // Metabolism\WordpressLoader compatibility
        $context['system'] = [
        	'head'   => $context['wp_head'],
	        'footer' => $context['wp_footer']
	    ];

        $context['page_title']  = empty($context['wp_title']) ? get_bloginfo('name') : $context['wp_title'];

        return $context;
    }


    public function fetch($path, $context=[], $expires=false)
    {
	    $context = array_merge(Timber::get_context(), $context);
	    $response = \Timber::fetch('page/' . $path, $context, $expires);

	    if( !$response )
	    	wp_die('Page ' . $path. ' not found');

	    return $response;
    }


    public function addToTwig($twig)
    {
        if ( class_exists( '\FrontBundle\Helper\TwigHelper' ) )
            $twig->addExtension( new \FrontBundle\Helper\TwigHelper( get_option('home') ) );
        elseif ( class_exists( '\Metabolism\WordpressLoader\Helper\TwigHelper' ) )
            $twig->addExtension( new \Metabolism\WordpressLoader\Helper\TwigHelper( get_option('home') ) );

        return $twig;
    }
}
