<?php

namespace Metabolism\WordpressBundle\Controller;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class AdminController {

	private $config;

	/**
	 * @var string plugin domain name for translations
	 */
	public static $acf_folder, $languages_folder;

	public static $bo_domain_name = 'bo_default';


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
	private function loadConfig()
	{
		global $_config;

		$this->config = $_config;

		self::$bo_domain_name   = 'bo_'.$this->config->get('domain_name', 'customer');
		self::$languages_folder = BASE_URI . '/config/languages';
	}


	public function __construct()
	{
		if( defined('WP_INSTALLING') && WP_INSTALLING )
			return;

		$this->loadConfig();
		$this->registerFilters();

		add_action( 'admin_init', [$this, 'init'] );
	}
}
