<?php

namespace Metabolism\WordpressBundle\Controller;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class AdminController {

	/**
	 * Init placeholder
	 */
	public function init(){}


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

		add_action( 'admin_init', [$this, 'init'] );
	}
}
