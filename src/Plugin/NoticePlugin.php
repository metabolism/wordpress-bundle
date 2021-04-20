<?php

namespace Metabolism\WordpressBundle\Plugin;


use Metabolism\WordpressBundle\Traits\SingletonTrait;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class NoticePlugin {

	use SingletonTrait;

	protected $config;


	/**
	 * Check symlinks and folders
	 */
	public function adminNotices(){

		if( !WP_DEBUG )
			return;

		global $wpdb, $table_prefix;

		if( ($_GET['fix']??false) == 'database' ){
			$wpdb->update($table_prefix."options", ['option_value' => WP_SITEURL], ['option_name' => 'siteurl']);
			$wpdb->update($table_prefix."options", ['option_value' => WP_HOME], ['option_name' => 'home']);
		}

		if( ($_GET['fix']??false) == 'controller' ){

            $controller = BASE_URI.'/src/Controller/'.$this->config->get('extra_permastructs.controller', 'BlogController').'.php';
            $template = BASE_URI.'/src/templates/generic.html.twig';

            if( !file_exists($controller) )
                copy(__DIR__.'/../../samples/controller/BlogController.php', $controller);

            if( !file_exists($template) )
                copy(__DIR__.'/../../samples/template/generic.html.twig', $template);
		}

		if( ($_GET['fix']??false) == 'service' ){

            $context = BASE_URI.'/src/Service/Context.php';

            if( !file_exists($context) ){

                if(!is_dir(BASE_URI.'/src/Service') )
                    mkdir(BASE_URI.'/src/Service');

                copy(__DIR__.'/../../samples/service/Context.php', $context);
            }
		}

		$notices = [];
		$errors = [];

		//check folder right
		$folders = [PUBLIC_DIR.'/wp-bundle/languages', PUBLIC_DIR.'/uploads', PUBLIC_DIR.'/wp-bundle/upgrade', '/var/cache', '/var/log'];
		$folders = apply_filters('wp-bundle/admin_notices', $folders);

		foreach ($folders as $folder ){

			$path = BASE_URI.$folder;

			if( !file_exists($path) && !@mkdir($path, 0755, true) )
				$errors [] = 'Can\' create folder : '.$folder;

			if( file_exists($path) && !is_writable($path) )
				$errors [] = $folder.' folder is not writable';
		}

		$siteurl = $wpdb->get_var("SELECT option_value FROM `".$table_prefix."options` WHERE `option_name` = 'siteurl'");
		$homeurl = $wpdb->get_var("SELECT option_value FROM `".$table_prefix."options` WHERE `option_name` = 'home'");

		if( str_replace('/edition','', $siteurl) !== str_replace('/edition','', $homeurl) )
			$notices[] = 'Site url host and Home url host are different, please check your database configuration';

		if( strpos($homeurl, '/edition' ) !== false )
			$notices[] = 'Home url must not contain /edition, please check your database configuration';

		if( strpos($homeurl, WP_HOME ) === false )
			$notices[] = 'Home url host is different from current host, please check your database configuration';

		if( strpos($siteurl, WP_HOME ) === false )
			$notices[] = 'Site url host is different from current host, please check your database configuration';

		if( !empty($notices))
			$notices[] = '<a href="?fix=database">Fix database now</a>';

		if( is_blog_installed() && (!isset($_SERVER['WP_INSTALLED']) || !$_SERVER['WP_INSTALLED']) )
			$notices[] = 'Wordpress is now installed, you should add WP_INSTALLED=1 to the <i>.env</i>';

		if( !file_exists(BASE_URI.'/src/Controller/'.$this->config->get('extra_permastructs.controller', 'BlogController').'.php') )
            $errors[] = 'There is no controller defined : <a href="?fix=controller">Create one</a>';

		if( !file_exists(BASE_URI.'/src/Service/Context.php') )
            $errors[] = 'There is no context service defined : <a href="?fix=service">Create one</a>';

		if( !empty($errors) )
			echo '<div class="error"><p>'.implode('<br/>', $errors ).'</p></div>';

		if( !empty($notices) )
			echo '<div class="updated"><p>'.implode('<br/>', $notices ).'</p></div>';
	}


	/**
	 * Add debug info
	 */
	public function debugInfo(){

		add_action( 'admin_bar_menu', function( $wp_admin_bar )
		{
			$args = [
				'id'    => 'debug',
				'title' => __('Debug').' : '.( WP_DEBUG ? __('On') : __('Off'))
			];

			$wp_admin_bar->add_node( $args );

		}, 999 );
	}


	/**
	 * remove wpdb error
	 */
	public function suppressError(){

		global $wpdb;
		$wpdb->suppress_errors = true;
	}


	/**
	 * NoticePlugin constructor.
	 * @param $config
	 */
	public function __construct($config)
	{
		$this->config = $config;

		if( is_admin() )
		{
			add_action( 'admin_notices', [$this, 'adminNotices']);

			if( WP_DEBUG )
				add_action( 'init', [$this, 'debugInfo']);
		}
		else{

			if( HEADLESS )
				add_action( 'init', [$this, 'suppressError']);
		}
	}
}
