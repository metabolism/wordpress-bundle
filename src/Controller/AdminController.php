<?php

namespace Metabolism\WordpressBundle\Controller;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class AdminController {

	protected $config;


	/**
	 * Init placeholder
	 */
	public function init(){}


	/**
	 * Allows user to add specific process on Wordpress functions
	 */
	public function registerFilters()
	{
		add_filter('update_right_now_text', function($text){
			return substr($text, 0, strpos($text, '%1$s')+4);
		});
	}


	/**
	 * Load App configuration
	 */
	public function loadConfig()
	{
		global $_config;
		$this->config = $_config;
	}


	/**
	 * Prevent Backend access based on ip whitelist
	 */
	private function lock()
	{
		$whitelist = getenv('ADMIN_IP_WHITELIST');

		if( $whitelist ){

			$whitelist = array_map('trim', explode(',', $whitelist));

			if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) )
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			else
				$ip = $_SERVER['REMOTE_ADDR'];

			if( !in_array($ip, $whitelist) )
				wp_die('Sorry, you are not allowed to access this page. Your IP: '.$ip);
		}
	}


	public function __construct()
	{
		if( defined('WP_INSTALLING') && WP_INSTALLING )
			return;

		$this->lock();

		$this->registerFilters();

		add_action( 'init', [$this, 'loadConfig'] );
		add_action( 'admin_init', [$this, 'init'] );
	}
}
