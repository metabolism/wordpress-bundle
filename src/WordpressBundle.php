<?php

namespace Metabolism\WordpressBundle;

use Metabolism\WordpressBundle\DependencyInjection\WordpressBundleExtension;
use Metabolism\WordpressBundle\Extension\TwigExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function Env\env;

class WordpressBundle extends Bundle
{
    private $root_dir;
    private $wp_path = "public/edition/";

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $ext = new WordpressBundleExtension([],$container);
    }

	public function boot()
	{
	    if( !isset($_SERVER['SERVER_NAME'] ) && (!isset($_SERVER['WP_INSTALLED']) || !$_SERVER['WP_INSTALLED']) )
	        return;

	    if( !isset($_SERVER['REQUEST_METHOD']) )
            $_SERVER['REQUEST_METHOD'] = 'GET';

        if( !isset($_SERVER['HTTP_HOST']) ) {

            if( isset($_SERVER['SERVER_NAME']) )
                $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'];
            elseif( $multisite = env('WP_MULTISITE') )
                $_SERVER['HTTP_HOST'] = $multisite;
            else
                $_SERVER['HTTP_HOST'] = 'localhost';
        }

		$this->root_dir = $this->container->get('kernel')->getProjectDir();

        $this->loadWordpress();

        //todo: use dependency injection
		if( $this->container->has('twig') ){

			$twig = $this->container->get('twig');

			$twigExtension = new TwigExtension();
			$twig->addExtension($twigExtension);
		}
	}

    /**
     * 	@see wp-includes/class-wp.php, main function
     */
    private function loadWordpress(){

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
