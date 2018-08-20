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

		if( !$role_object->has_cap('wpseo_edit_advanced_metadata') )
			$role_object->add_cap( 'editor', 'wpseo_edit_advanced_metadata' );

		if( !$role_object->has_cap('wpseo_manage_options') )
			$role_object->add_cap( 'editor', 'wpseo_manage_options' );
	}



	/**
	 * Allows user to add specific process on Wordpress functions
	 */
	public function registerFilters()
	{
		add_filter('woocommerce_template_path', function($array){ return '../../../woocommerce/'; });

		add_filter('wp_calculate_image_srcset_meta', '__return_null');
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
		if( defined('WP_INSTALLING') and WP_INSTALLING )
			return;

		$this->loadConfig();
		$this->registerFilters();

		add_action( 'admin_init', [$this, 'updateEditorRole'] );

		// Remove image sizes for thumbnails
		add_filter( 'intermediate_image_sizes_advanced', [$this, 'intermediateImageSizesAdvanced'] );
	}
}
