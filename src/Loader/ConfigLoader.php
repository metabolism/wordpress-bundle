<?php

namespace Metabolism\WordpressBundle\Loader;

use Dflydev\DotAccessData\Data;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

use Env\Env;
use function Env\env;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class ConfigLoader {

    public function get($resource, $default=false){

        global $_config;
        return $_config->get($resource, $default);
    }

    public function import($resource)
    {
        /**
         * Wordpress configuration file
         */

        if (!defined('BASE_URI'))
        {
            $base_uri = dirname( __DIR__ );

            if( '\\' === \DIRECTORY_SEPARATOR ){
                // windows
                $base_uri = preg_replace( "/\\\public$/", '', $base_uri );
                $base_uri = preg_replace( "/\\\\vendor\\\\metabolism\\\\wordpress-bundle\\\\src$/", '', $base_uri );
            }
            else{
                // unix
                $base_uri = preg_replace( "/\/public$/", '', $base_uri );
                $base_uri = preg_replace( "/\/vendor\/metabolism\/wordpress-bundle\/src$/", '', $base_uri );
            }

            define( 'BASE_URI', $base_uri);
        }

        /**
         * Load App configuration
         */
        global $_config;

        try{

            $config = Yaml::parseFile($resource);
        }
        catch (ParseException $e){

            die(basename($resource).' loading error: '.$e->getMessage());
        }

        $_config = new Data($config);

        /**
         * Set env default
         */
        $env = env('APP_ENV')?:(env('WP_ENV')?:'dev');


        /**
         * Define constant
         */
        foreach ($_config->get('define', []) as $constant=>$value)
            define( strtoupper($constant), $value);

        /**
         * Define basic environment
         */
        define( 'WP_ENV', $env);
        define( 'WP_DEBUG', $env === 'dev');
        define( 'WP_DEBUG_DISPLAY', WP_DEBUG);

        define( 'HEADLESS', $_config->get('headless') );
        define( 'URL_MAPPING', $_config->get('headless.mapping')?env('MAPPED_URL'):false );

        /**
         * Enable multisite
         */
        if( $_config->get('multisite') || env('MULTISITE') )
        {
            define( 'MULTISITE', true );
            define( 'SUBDOMAIN_INSTALL', $_config->get('multisite.subdomain_install') );
            define( 'DOMAIN_CURRENT_SITE', $_config->get('multisite.domain', $_SERVER['HTTP_HOST']));
            define( 'SITE_ID_CURRENT_SITE', $_config->get('multisite.site_id', 1));
            define( 'BLOG_ID_CURRENT_SITE', $_config->get('multisite.blog_id', 1));
        }
        elseif( $_config->get('install-multisite') )
        {
            define( 'WP_ALLOW_MULTISITE', true );
        }

        /**
         * Configure URLs
         */

        if( !$wp_home = env('WP_HOME') ){

            $isSecure = false;

            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'){
                $isSecure = true;
            }
            elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on'){
                $isSecure = true;
                $_SERVER['HTTPS']='on';
            }

            $wp_home = ( $isSecure ? 'https' : 'http' ) . '://'.trim($_SERVER['HTTP_HOST'], '/');
        }

        define( 'WP_FOLDER', '/edition' );

        if( !defined('WP_HOME') )
            define( 'WP_HOME', $wp_home);

        if( !$wp_siteurl = env('WP_SITEURL') )
            $wp_siteurl = WP_HOME.WP_FOLDER;

        define( 'WP_SITEURL', $wp_siteurl);

        if(filter_var($_SERVER['SERVER_NAME'], FILTER_VALIDATE_IP) !== false)
            define('COOKIE_DOMAIN', '' );
        else
            define( 'COOKIE_DOMAIN', strtok($_SERVER[ 'HTTP_HOST' ], ':') );

        /**
         * Define DB settings
         */
        if( !env('DATABASE_URL') && !env('DB_NAME') )
            die('<code>Database configuration is missing, please add <b>DATABASE_URL=mysql://user:pwd@localhost:3306/dbname</b> to your environment</code>');

        if( $database_url = env('DATABASE_URL') ){

            $mysql = explode('@', str_replace('mysql://', '', str_replace('mariadb://', '', $database_url)));
            $mysql[0] = explode(':', $mysql[0]);
            $mysql[1] = explode('/', $mysql[1]);

            define( 'DB_NAME', $mysql[1][1]);
            define( 'DB_USER', $mysql[0][0]);
            define( 'DB_PASSWORD', $mysql[0][1]);
            define( 'DB_HOST', $mysql[1][0]);
        }
        else{

            define( 'DB_NAME', env('DB_NAME'));
            define( 'DB_USER', env('DB_USER'));
            define( 'DB_PASSWORD', env('DB_PASSWORD'));
            define( 'DB_HOST', env('DB_HOST'));
            define( 'DB_PORT', env('DB_PORT'));
        }

        define( 'DB_CHARSET', $_config->get('database.charset', 'utf8mb4'));
        define( 'DB_COLLATE', $_config->get('database.collate', ''));


        /**
         * Authentication Unique Keys and Salts
         */
        define( 'AUTH_KEY', env('AUTH_KEY'));
        define( 'SECURE_AUTH_KEY', env('SECURE_AUTH_KEY'));
        define( 'LOGGED_IN_KEY', env('LOGGED_IN_KEY'));
        define( 'NONCE_KEY', env('NONCE_KEY'));

        define( 'AUTH_SALT', env('AUTH_SALT'));
        define( 'SECURE_AUTH_SALT', env('SECURE_AUTH_SALT'));
        define( 'LOGGED_IN_SALT', env('LOGGED_IN_SALT'));
        define( 'NONCE_SALT', env('NONCE_SALT'));


        /**
         * Redefine cookie name without wordpress
         */
        define( 'COOKIEHASH',           md5( WP_SITEURL )    );

        if( $cookie_prefix = env('COOKIE_PREFIX') ) {

            define('USER_COOKIE', $cookie_prefix . '_user_' . COOKIEHASH);
            define('PASS_COOKIE', $cookie_prefix . '_pass_' . COOKIEHASH);
            define('AUTH_COOKIE', $cookie_prefix . '_' . COOKIEHASH);
            define('SECURE_AUTH_COOKIE', $cookie_prefix . '_sec_' . COOKIEHASH);
            define('LOGGED_IN_COOKIE', $cookie_prefix . '_logged_in_' . COOKIEHASH);
            define('TEST_COOKIE', 'test_cookie_' . COOKIEHASH);
        }

        /**
         * Custom Content Directory
         */

        if (!defined('PUBLIC_DIR'))
            define( 'PUBLIC_DIR', '/public');

        if (!defined('WP_CONTENT_DIR'))
            define( 'WP_CONTENT_DIR', BASE_URI . PUBLIC_DIR . '/wp-bundle');

        if (!defined('UPLOADS'))
            define( 'UPLOADS', '../uploads');

        if (!defined('WP_PLUGIN_DIR'))
            define('WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins');

        if (!defined('WPMU_PLUGIN_DIR'))
            define('WPMU_PLUGIN_DIR', WP_CONTENT_DIR.'/mu-plugins');


        if (!defined('WP_CONTENT_URL'))
            define( 'WP_CONTENT_URL', WP_HOME.'/wp-bundle' );

        define('WP_UPLOADS_DIR', realpath(WP_CONTENT_DIR.'/'.UPLOADS));

        /**
         * Custom Settings
         */
        if (!defined('DISALLOW_FILE_EDIT'))
            define( 'DISALLOW_FILE_EDIT', true);

        if (!defined('WP_POST_REVISIONS'))
            define( 'WP_POST_REVISIONS', 3);

        if (!defined('DISABLE_WP_CRON'))
            define('DISABLE_WP_CRON', true);

        /**
         * Bootstrap WordPress
         */

        if (!defined('WP_USE_THEMES'))
            define( 'WP_USE_THEMES', false);

        if (!defined('ABSPATH'))
            define( 'ABSPATH', BASE_URI . PUBLIC_DIR . WP_FOLDER .'/');
    }
}
