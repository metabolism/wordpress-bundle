<?php

namespace Metabolism\WordpressBundle;

use Metabolism\WordpressBundle\Extension\TwigExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class WordpressBundle extends Bundle
{
    private $root_dir;
    private $public_dir;
    private $log_dir;

    private $wp_path = "public/edition/";

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

	public function boot()
	{
	    if( !isset($_SERVER['SERVER_NAME'] ) && (!isset($_SERVER['WP_INSTALLED']) || !$_SERVER['WP_INSTALLED']) )
	        return;

		$this->log_dir = $this->container->get('kernel')->getLogDir();
		$this->root_dir = $this->container->get('kernel')->getProjectDir();
		$this->public_dir = $this->root_dir.(is_dir($this->root_dir.'/public') ? '/public' : '/web');

        $this->resolveServer();
        $this->loadWordpress();

        //todo: use dependency injection
		if( $this->container->has('twig') ){

			$twig = $this->container->get('twig');

			$twigExtension = new TwigExtension();
			$twig->addExtension($twigExtension);
		}
	}


    private function resolveServer(){

        if( !isset($_SERVER['REQUEST_METHOD']) )
            $_SERVER['REQUEST_METHOD'] = 'GET';

        if( !isset($_SERVER['HTTP_HOST']) ) {

            if( $host = ($_SERVER['WP_MULTISITE']??false) ){

                $_SERVER['HTTP_HOST'] = $host;
            }
            elseif( $host = ($_SERVER['SERVER_NAME']??false) ){

                $_SERVER['HTTP_HOST'] = $host;
            }
            else{

                $_SERVER['HTTP_HOST'] = '127.0.0.1:8000';
                $_SERVER['SERVER_PORT'] = '8000';
            }
        }
    }

    /**
     * 	@see wp-includes/class-wp.php, main function
     */
    private function loadWordpress(){

        if( !file_exists($this->public_dir.'/wp-config.php') )
            return;

        global $request;

        if( is_object($request) && get_class($request) == 'Symfony\Component\HttpFoundation\Request' )
            trigger_error('$request must be renamed in "'.$this->public_dir.'/index.php" : $httpRequest = Request::createFromGlobals();', E_USER_WARNING);

        if( !defined('WP_DEBUG_LOG') )
            define('WP_DEBUG_LOG', $this->log_dir);

        $composer = $this->root_dir.'/composer.json';

        // get Wordpress path
        if( file_exists($composer) ){

            $composer = json_decode(file_get_contents($composer), true);
            $installer_paths= $composer['extra']['installer-paths']??[];

            foreach ($installer_paths as $installer_path=>$types){

                if( in_array("type:wordpress-core", $types) )
                    $this->wp_path = $installer_path;
            }
        }

        // start loading Wordpress core without theme support
        $wp_load_script = $this->root_dir.'/'.$this->wp_path.'wp-load.php';

        if( !file_exists($wp_load_script) )
            return;

        include $wp_load_script;

        global $wp;

        $wp->init();
        $wp->parse_request();
        $wp->query_posts();
        $wp->register_globals();

        do_action_ref_array( 'wp', array( &$wp ) );

        remove_action( 'template_redirect', 'redirect_canonical' );
        do_action( 'template_redirect' );
    }
}
