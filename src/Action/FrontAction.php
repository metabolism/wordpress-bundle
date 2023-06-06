<?php

namespace Metabolism\WordpressBundle\Action;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class FrontAction {

	/**
	 * @var string plugin domain name for translations
	 */
	public static $languages_folder;

	public static $domain_name = 'default';

	/**
	 * Redirect to admin
	 */
	public function redirect()
	{
		if( defined('DOING_CRON') && DOING_CRON )
			return;

		$path = rtrim($_SERVER['REQUEST_URI'], '/');

		if( !empty($path) && strpos($path, WP_FOLDER) !== false && 'POST' !== $_SERVER['REQUEST_METHOD'] ){

			wp_redirect(is_user_logged_in() ? admin_url('index.php') : wp_login_url());
			exit;
		}
	}


	/**
	 * Init placeholder
	 */
	public function init(){}


	/**
	 * Loaded placeholder
	 */
	public function loaded(){}


	public function __construct()
	{
		if( defined('WP_INSTALLING') && WP_INSTALLING )
			return;

        add_action( 'kernel_loaded', [$this, 'loaded']);
        add_action( 'init', [$this, 'init']);
		add_action( 'init', [$this, 'redirect']);
		add_action( 'init', '_wp_admin_bar_init', 0 );
	}
}
