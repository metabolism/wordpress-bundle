<?php

namespace Metabolism\WordpressBundle\Loader;

use Dflydev\DotAccessData\Data;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;


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
			$base_uri = preg_replace( "/\\web$/", '', $base_uri );
			$base_uri = preg_replace( "/\\\\vendor\\\\metabolism\\\\wordpress-bundle\\\\src$/", '', $base_uri );
			}
			else{
				// unix
				$base_uri = preg_replace( "/\/web$/", '', $base_uri );
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
		$env = $_SERVER['APP_ENV'] ?? 'dev';


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

		$support = $_config->get('support', []);
		define( 'WP_FRONT', in_array('templates', $support)||in_array('template', $support) );

		/**
		 * Enable multisite
		 */
		if( $_config->get('multisite') && (!isset($_ENV['MULTISITE']) || getenv('MULTISITE')) )
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
		$isSecure = false;

		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'){
			$isSecure = true;
		}
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on'){
			$isSecure = true;
			$_SERVER['HTTPS']='on';
		}

		$base_uri = ( $isSecure ? 'https' : 'http' ) . '://'.trim($_SERVER['HTTP_HOST'], '/');

		define( 'WP_FOLDER', '/edition' );

		if( !defined('WP_HOME') )
			define( 'WP_HOME', $base_uri);

		define( 'WP_SITEURL', WP_HOME.WP_FOLDER);

        if(filter_var($_SERVER['SERVER_NAME'], FILTER_VALIDATE_IP) !== false)
            define('COOKIE_DOMAIN', '' );
        else
            define( 'COOKIE_DOMAIN', $_SERVER[ 'HTTP_HOST' ] );

		/**
		 * Define DB settings
		 */
		if( !isset($_SERVER['DATABASE_URL']) )
			die('DATABASE_URL is missing in env');

		$mysql = explode('@', str_replace('mysql://', '', $_SERVER['DATABASE_URL']));
		$mysql[0] = explode(':', $mysql[0]);
		$mysql[1] = explode('/', $mysql[1]);

		define( 'DB_NAME', $mysql[1][1]);
		define( 'DB_USER', $mysql[0][0]);
		define( 'DB_PASSWORD', $mysql[0][1]);
		define( 'DB_HOST', $mysql[1][0]);
		define( 'DB_CHARSET', $_config->get('database.charset', 'utf8mb4'));
		define( 'DB_COLLATE', $_config->get('database.collate', ''));


		/**
		 * Authentication Unique Keys and Salts
		 */
		define( 'AUTH_KEY', $_config->get('key.auth','O-} !h|JpOq^w,CXn+O5=o3MvkN_So+ O0-chs$+a>KJq*i~/!ykEd<]IsPdgI#6'));
		define( 'SECURE_AUTH_KEY', $_config->get('key.secure_auth','r HvS?mdf^4xc.Iy^G*<ZliwL5r_w.]CUWIu|j0{sfq-M)k:Lhi-),qCDcN<Yy+w'));
		define( 'LOGGED_IN_KEY', $_config->get('key.logged_in','u`UAyT)Wp0bT&Z.^e3RWTWDs?Je9K0UBQDJqG$W*yb9YG1yl,|*:LQV^ZUt|Q~#.'));
		define( 'NONCE_KEY', $_config->get('key.nonce','cV9Q^z7H{oI>H6>>vLHQYB[)1N&#ur(# Iqw*k?r-FkQ+#eo9<R^1N?uo.*N~!J5'));

		define( 'AUTH_SALT', $_config->get('salt.auth','w9J/dNw/bv}@Z#/YcrjPcH$^_[ni&4tji0JA0?na}yTw#0}yuZW>BXDVVjVGA+vk'));
		define( 'SECURE_AUTH_SALT', $_config->get('salt.secure_auth','T7ntE>-j*2G3Qosn;0?|7{aqs&SU) }_S ~6f5k~PTedeX^jNe&T h)9(k4nT2Rq'));
		define( 'LOGGED_IN_SALT', $_config->get('salt.logged_in','w/iowiks]_i5b#/SqYuD2`28o</-L|P4H3vq@!<OrH 7Q!gxB[Q4m`/*CiVdylGs'));
		define( 'NONCE_SALT', $_config->get('salt.nonce','gelPRQb4NzO=4pOG_5YnuN(5G~YJCIutY*BL%!:ds(TqwDd;F[PsI,gT_1J-9;;D'));


		/**
		 * Redefine cookie name without wordpress
		 */
		define( 'COOKIEHASH',           md5( WP_SITEURL )    );

		define( 'USER_COOKIE',          $_SERVER['COOKIE_PREFIX'].'_user_'      . COOKIEHASH );
		define( 'PASS_COOKIE',          $_SERVER['COOKIE_PREFIX'].'_pass_'      . COOKIEHASH );
		define( 'AUTH_COOKIE',          $_SERVER['COOKIE_PREFIX'].'_'           . COOKIEHASH );
		define( 'SECURE_AUTH_COOKIE',   $_SERVER['COOKIE_PREFIX'].'_sec_'       . COOKIEHASH );
		define( 'LOGGED_IN_COOKIE',     $_SERVER['COOKIE_PREFIX'].'_logged_in_' . COOKIEHASH );
		define( 'TEST_COOKIE',          'test_cookie_'.COOKIEHASH                            );


		/**
		 * Custom Content Directory
		 */

		if (!defined('PUBLIC_DIR'))
			define( 'PUBLIC_DIR', '/web');

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
