<?php

namespace Metabolism\WordpressBundle;

use Env\Env;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Routing\Router;
use function Env\env;

class WordpressBundle extends Bundle
{
    private $root_dir;
    private $public_dir;
    private $log_dir;
    private Router $router;

    private $wp_path = "public/edition/";

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function boot()
    {
        Env::$options = Env::USE_ENV_ARRAY;

		$kernel = $this->container->get('kernel');

        $this->log_dir = $kernel->getLogDir();
        $this->root_dir = $kernel->getProjectDir();
        $this->router = $this->container->get('router');

        $this->public_dir = $this->root_dir.(is_dir($this->root_dir.'/public') ? '/public' : '/web');

        $this->resolveServer();
        $this->loadWordpress();
    }


    private function resolveServer(){

		$context = $this->router->getContext();

        if( !isset($_SERVER['HTTP_HOST']) ) {

            if( $host = env('WP_MULTISITE') ){

                $_SERVER['HTTP_HOST'] = $host;
            }
            elseif( $host = ($_SERVER['SERVER_NAME']??false) ){

                $_SERVER['HTTP_HOST'] = $host;
            }
            else{

	            $_SERVER['SERVER_PORT'] = $context->isSecure() ? $context->getHttpsPort() : $context->getHttpPort();

				if( $context->isSecure() )
					$_SERVER['HTTP_HOST'] = $context->getHost().($context->getHttpPort()!=443?':'.$context->getHttpsPort():'');
	            else
		            $_SERVER['HTTP_HOST'] = $context->getHost().($context->getHttpPort()!=80?':'.$context->getHttpPort():'');
            }
        }
	}

    public static function loadPlugins (){

        $plugins = scandir(__DIR__.'/Plugin');

        foreach($plugins as $plugin){

            if( !in_array($plugin, ['.','..']) )
            {
                $classname = '\Metabolism\WordpressBundle\Plugin\\'.str_replace('.php', '', $plugin);

                if( class_exists($classname) )
                    new $classname();
            }
        }
    }

    public static function isLoginUrl(){

        $uri = explode('/', $_SERVER['SCRIPT_NAME']);
        $page = end($uri);

        return in_array( $page, ['wp-login.php', 'wp-signup.php'] );
    }

    /**
     * 	@see wp-includes/class-wp.php, main function
     */
    private function loadWordpress(){

        if( !file_exists($this->public_dir.'/wp-config.php') )
            return;

        if (!defined('WP_DEBUG_LOG'))
            define('WP_DEBUG_LOG', realpath($this->log_dir . '/wp-errors.log'));

        $composer = $this->root_dir.'/composer.json';

        // get WordPress path
        if( !is_dir($this->root_dir.'/'.$this->wp_path) && file_exists($composer) ){

            $composer = json_decode(file_get_contents($composer), true);
            $installer_paths= $composer['extra']['installer-paths']??[];

            foreach ($installer_paths as $installer_path=>$types){

                if( in_array("type:wordpress-core", $types) )
                    $this->wp_path = $installer_path;
            }
        }

        // start loading WordPress core without theme support
        $wp_load_script = $this->root_dir.'/'.$this->wp_path.'wp-load.php';

        if( !file_exists($wp_load_script) )
            return;

        include $wp_load_script;

        remove_action( 'template_redirect', 'redirect_canonical' );
    }
}
