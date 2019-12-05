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

		$notices = [];
		$errors = [];

		//check folder right
		$folders = [PUBLIC_DIR.'/wp-bundle/languages', PUBLIC_DIR.'/uploads', PUBLIC_DIR.'/uploads/acf-thumbnails', PUBLIC_DIR.'/wp-bundle/upgrade', '/config/acf-json', '/var/cache', '/var/log'];
		$folders = apply_filters('wp-bundle/admin_notices', $folders);

		foreach ($folders as $folder ){

			$path = BASE_URI.$folder;

			if( !file_exists($path) )
                $errors [] = $folder.' folder doesn\'t exist';
			elseif( !is_writable($path) )
                $errors [] = $folder.' folder is not writable';
		}

		global $wpdb, $table_prefix;
		$siteurl = $wpdb->get_var("SELECT option_value FROM `".$table_prefix."options` WHERE `option_name` = 'siteurl'");
		$homeurl = $wpdb->get_var("SELECT option_value FROM `".$table_prefix."options` WHERE `option_name` = 'home'");

		if( str_replace('/edition','', $siteurl) !== str_replace('/edition','', $homeurl) )
			$notices [] = 'Site url host and Home url host are different, please check your database configuration';

		if( strpos($homeurl, '/edition' ) !== false )
			$notices [] = 'Home url must not contain /edition, please check your database configuration';

		if( strpos($homeurl, $_SERVER['HTTP_HOST'] ) === false )
			$notices [] = 'Home url host is different from current host, please check your database configuration';

		if( strpos($siteurl, $_SERVER['HTTP_HOST'] ) === false )
			$notices [] = 'Site url host is different from current host, please check your database configuration';

        if( is_blog_installed() && (!isset($_SERVER['WP_INSTALLED']) || !$_SERVER['WP_INSTALLED']) )
            $notices [] = 'Wordpress is now installed, you should add WP_INSTALLED=1 to the <i>.env</i> file';

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
