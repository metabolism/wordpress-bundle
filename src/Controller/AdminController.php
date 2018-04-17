<?php

namespace Metabolism\WordpressBundle\Controller;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class AdminController {


	/**
	 * @var string plugin domain name for translations
	 */
	public static $acf_folder, $languages_folder;

	public static $bo_domain_name = 'bo_default';


	/**
	 * Application Constructor
	 */
	public function setup()
	{
		if( defined('WP_INSTALLING') and WP_INSTALLING )
			return;

		$this->loadConfig();
		$this->registerFilters();

		add_action( 'admin_init', [$this, 'updateEditorRole'] );

		// Remove image sizes for thumbnails
		add_filter( 'intermediate_image_sizes_advanced', [$this, 'intermediateImageSizesAdvanced'] );
	}

	/**
	 * Unset thumbnail image
	 */
	public function intermediateImageSizesAdvanced($sizes)
	{
		unset($sizes['medium'], $sizes['medium_large'], $sizes['large']);
		return $sizes;
	}


	public function updateEditorRole()
	{
		$role_object = get_role( 'editor' );

		if( !$role_object->has_cap('edit_theme_options') )
			$role_object->add_cap( 'edit_theme_options' );
	}



	/**
	 * Allows user to add specific process on Wordpress functions
	 */
	public function registerFilters()
	{
		add_filter('woocommerce_template_path', function($array){ return '../../../woocommerce/'; });

		add_filter('acf/settings/save_json', function(){ return $this::$acf_folder; });
		add_filter('acf/settings/load_json', function(){ return [$this::$acf_folder]; });

		add_filter('wp_calculate_image_srcset_meta', '__return_null');
		add_filter('update_right_now_text', function($text){
			return substr($text, 0, strpos($text, '%1$s')+4);
		});

		// Handle subfolder in url
		add_filter('option_siteurl', [$this, 'optionSiteURL'] );
		add_filter('network_site_url', [$this, 'networkSiteURL'] );
	}


	/**
	 * Add edition folder to option url
	 */
	public function networkSiteURL($url)
	{
		if( WP_FOLDER && strpos($url,WP_FOLDER) === false )
			return str_replace('/wp-admin', WP_FOLDER.'/wp-admin', $url);
		else
			return $url;
	}


	/**
	 * Add edition folder to option url
	 */
	public function optionSiteURL($url)
	{
		if( WP_FOLDER )
			return strpos($url, WP_FOLDER) === false ? $url.WP_FOLDER : $url;
		else
			return $url;
	}


	/**
	 * Load App configuration
	 */
	private function loadConfig()
	{
		global $_config;

		$this->config = $_config;

		self::$bo_domain_name   = 'bo_'.$this->config->get('domain_name', 'customer');
		self::$acf_folder       = BASE_URI . '/config/acf-json';
		self::$languages_folder = BASE_URI . '/config/languages';
	}


	public function __construct()
	{
		$this->setup();
	}
}
