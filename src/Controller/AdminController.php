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
		add_filter('woocommerce_template_path', function($array){ return '../../../WoocommerceBundle/'; });

		add_filter('acf/settings/save_json', function(){ return $this::$acf_folder; });
		add_filter('acf/settings/load_json', function(){ return [$this::$acf_folder]; });

		add_filter('wp_calculate_image_srcset_meta', '__return_null');

		add_filter( "site_option_siteurl", function($value){
			return str_replace('/ajax.php/', '/', $value);
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
		if( WP_SUBFOLDER && strpos($url,WP_SUBFOLDER) === false )
			return str_replace('/wp-admin', WP_SUBFOLDER.'/wp-admin', $url);
		else
			return $url;
	}


	/**
	 * Add edition folder to option url
	 */
	public function optionSiteURL($url)
	{
		if( WP_SUBFOLDER )
			return strpos($url, WP_SUBFOLDER) === false ? $url.WP_SUBFOLDER : $url;
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
		self::$acf_folder       = WP_CONTENT_DIR . '/acf-json';
		self::$languages_folder = WP_CONTENT_DIR . '/languages';
	}


	public function __construct()
	{
		$this->setup();
	}
}
