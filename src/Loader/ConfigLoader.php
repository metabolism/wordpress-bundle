<?php

namespace Metabolism\WordpressBundle\Loader;

use Env\Env;
use Metabolism\WordpressBundle\Helper\PathHelper;
use Symfony\Component\HttpFoundation\Request;
use function Env\env;

class ConfigLoader{

    public static $loaded=false;

    public static function import($root_dir, $yaml_filepath='/config/packages/wordpress.yaml')
    {
        if( self::$loaded )
            return;

        Env::$options = Env::USE_ENV_ARRAY;

        if (!defined('BASE_URI')) {

            $base_uri = realpath($root_dir);
            define( 'BASE_URI', $base_uri);
        }

        if( !defined('WPS_YAML_FILE') )
            define('WPS_YAML_FILE', BASE_URI.$yaml_filepath);

        if( !defined('WPS_YAML_TRANSLATION_FILES') )
            define('WPS_YAML_TRANSLATION_FILES', BASE_URI.'/translations');

        /**
         * Get paths
         */
        $wp_path = PathHelper::getWordpressRoot(BASE_URI);

        $folders = array_filter(explode('/', $wp_path));

        $wp_folder = end($folders);
        $public_folder = reset($folders);

        /**
         * Set env default
         */
        $env = env('APP_ENV')?:(env('WP_ENV')?:'development');
        $env =  $env === 'dev' ? 'development' : $env;

        /**
         * Define basic environment
         */
        define( 'WP_ENV', $env);
        define( 'WP_DEBUG', $env === 'development');
        define( 'WP_DEBUG_DISPLAY', WP_DEBUG);
        define( 'SCRIPT_DEBUG', WP_DEBUG);
        define( 'WP_ENVIRONMENT_TYPE', $env);

        /**
         * Enable multisite
         */
        if( env('WP_MULTISITE') )
        {
            define( 'MULTISITE', true );
            define( 'SUBDOMAIN_INSTALL', env('SUBDOMAIN_INSTALL') );

            if( $domain_current_site = env('DOMAIN_CURRENT_SITE') ){

                define( 'DOMAIN_CURRENT_SITE', $domain_current_site);
                define( 'SITE_ID_CURRENT_SITE', env('SITE_ID_CURRENT_SITE')?:1);
                define( 'BLOG_ID_CURRENT_SITE', env('BLOG_ID_CURRENT_SITE')?:1);
                define( 'PATH_CURRENT_SITE', env('PATH_CURRENT_SITE')?:'/');
            }
        }
        else
        {
            define( 'WP_ALLOW_MULTISITE', true );
        }

        /**
         * Configure URLs
         */

        if( !$wp_home = env('WP_HOME') ) {

            $request = Request::createFromGlobals();
            $wp_home = $request->getSchemeAndHttpHost();
        }

        define( 'WP_FOLDER', '/'.$wp_folder);

        if( !defined('WP_HOME') )
            define( 'WP_HOME', $wp_home);

        if( !$wp_siteurl = env('WP_SITEURL') )
            $wp_siteurl = WP_HOME.WP_FOLDER;

        define( 'WP_SITEURL', $wp_siteurl);

        if(isset($_SERVER['SERVER_NAME']) && filter_var($_SERVER['SERVER_NAME'], FILTER_VALIDATE_IP) !== false)
            define('COOKIE_DOMAIN', '' );
        else
            define( 'COOKIE_DOMAIN', strtok($_SERVER[ 'HTTP_HOST' ], ':') );

        /**
         * Define DB settings
         */
        if( !env('DATABASE_URL') && !env('DB_NAME') )
            die('<code>Database configuration is missing, please add <b>DATABASE_URL=mysql://user:pwd@localhost:3306/dbname</b> to your environment or DB_NAME, DB_USER, DB_PASSWORD, DB_HOST separately.</code>');

        if( env('DB_NAME') ){

            define( 'DB_NAME', env('DB_NAME'));
            define( 'DB_USER', env('DB_USER'));
            define( 'DB_PASSWORD', env('DB_PASSWORD'));
            define( 'DB_HOST', env('DB_HOST'));
            define( 'DB_PORT', env('DB_PORT'));
        }
        else{

            $database_url = env('DATABASE_URL');

            define( 'DB_NAME', trim(parse_url($database_url,PHP_URL_PATH), '/'));
            define( 'DB_USER', parse_url($database_url,PHP_URL_USER));
            define( 'DB_PASSWORD', parse_url($database_url,PHP_URL_PASS));
            define( 'DB_HOST',  parse_url($database_url,PHP_URL_HOST));
        }

        define( 'DB_CHARSET', env('DB_CHARSET')?:'utf8mb4');
        define( 'DB_COLLATE', env('DB_COLLATE')?:'utf8mb4_unicode_ci');


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
         * Redefine cookie name without WordPress
         */
        define( 'COOKIEHASH', md5( WP_SITEURL )    );

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
            define( 'PUBLIC_DIR', '/'.$public_folder);

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

        if (!defined('FS_METHOD'))
            define( 'FS_METHOD', env('FS_METHOD')?:'direct');

        if (!defined('DISALLOW_FILE_EDIT'))
            define( 'DISALLOW_FILE_EDIT', true);

        if (!defined('DISALLOW_FILE_MODS'))
            define( 'DISALLOW_FILE_MODS', true);

        if (!defined('EMPTY_TRASH_DAYS'))
            define('EMPTY_TRASH_DAYS', 7);

        if (!defined('WP_POST_REVISIONS'))
            define( 'WP_POST_REVISIONS', 3);

        if (!defined('DISABLE_WP_CRON'))
            define('DISABLE_WP_CRON', true);

        if (!defined('WP_DISABLE_FATAL_ERROR_HANDLER'))
            define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );

        if (!defined('WP_DEFAULT_THEME'))
            define('WP_DEFAULT_THEME', 'empty');

        foreach (['HEADLESS', 'URL_MAPPING', 'BUILD_HOOK', 'BUILD_BADGE', 'GOOGLE_MAP_API_KEY', 'GOOGLE_TRANSLATE_KEY', 'DEEPL_KEY'] as $key){

            if (!defined($key))
                define($key, env($key) );
        }

        /**
         * Bootstrap WordPress
         */

        if (!defined('WP_USE_THEMES'))
            define( 'WP_USE_THEMES', false);

        if (!defined('ABSPATH'))
            define( 'ABSPATH', BASE_URI . PUBLIC_DIR . WP_FOLDER .'/');

        self::$loaded = true;
    }
}
