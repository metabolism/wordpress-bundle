<?php

namespace Metabolism\WordpressBundle;

use Env\Env;
use Metabolism\WordpressBundle\Helper\PathHelper;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use function Env\env;

class WordpressBundle extends Bundle
{
    private $root_dir;
    private $public_dir;
    private $log_dir;
    private $router;

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

            $multisite = env('WP_MULTISITE');

            if( $multisite && php_sapi_name() == 'cli' ){

                $url = parse_url($multisite);

                $_SERVER['SERVER_PORT'] = $url['port']??80;
                $_SERVER['REQUEST_SCHEME'] = $url['scheme']??'https';

                if( $_SERVER['REQUEST_SCHEME'] == 'https' )
                    $_SERVER['HTTP_HOST'] = $url['host']??'127.0.0.1'.($_SERVER['SERVER_PORT']!=443?':'.$_SERVER['SERVER_PORT']:'');
                else
                    $_SERVER['HTTP_HOST'] = $url['host']??'127.0.0.1'.($_SERVER['SERVER_PORT']!=80?':'.$_SERVER['SERVER_PORT']:'');
            }
            else{

                $_SERVER['SERVER_PORT'] = $context->isSecure() ? $context->getHttpsPort() : $context->getHttpPort();
                $_SERVER['REQUEST_SCHEME'] = $context->isSecure() ? 'https' : 'http';

                if( $context->isSecure() )
                    $_SERVER['HTTP_HOST'] = $context->getHost().($context->getHttpsPort()!=443?':'.$context->getHttpsPort():'');
                else
                    $_SERVER['HTTP_HOST'] = $context->getHost().($context->getHttpPort()!=80?':'.$context->getHttpPort():'');
            }

            $_SERVER['HTTPS'] = $_SERVER['REQUEST_SCHEME'] == 'https' ? 'on' : 'off';
        }

        if( !isset($_SERVER['REMOTE_ADDR']) && php_sapi_name() == "cli" )
            $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
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

        // get WordPress path
        $wp_path = PathHelper::getWordpressRoot($this->root_dir);

        // start loading WordPress core without theme support
        $wp_load_script = $this->root_dir.'/'.$wp_path.'wp-load.php';

        if( !file_exists($wp_load_script) )
            return;

        include $wp_load_script;

        remove_action( 'template_redirect', 'redirect_canonical' );
    }
}
