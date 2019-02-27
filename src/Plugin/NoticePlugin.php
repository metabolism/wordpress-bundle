<?php

namespace Metabolism\WordpressBundle\Plugin;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class NoticePlugin {

	protected $config;


	/**
	 * Check symlinks and forders
	 */
	public function adminNotices(){

		if( !WP_DEBUG )
			return;

		$notices = [];

		//check folder wright
		foreach (['web/wp-bundle/languages', 'web/uploads', 'web/wp-bundle/upgrade', 'config/acf-json'] as $folder ){

			$path = BASE_URI.'/'.$folder;

			if( !file_exists($path) )
				$notices [] = $folder.' folder doesn\'t exist';
			elseif( !is_writable($path) )
				$notices [] = $folder.' folder is not writable';
		}

		if( !empty($notices) )
			echo '<div class="error"><p>'.implode('<br/>', $notices ).'</p></div>';
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

			if( !WP_FRONT)
				add_action( 'init', [$this, 'suppressError']);
		}
	}
}
