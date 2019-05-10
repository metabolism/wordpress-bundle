<?php

namespace Metabolism\WordpressBundle\Provider;

use Dflydev\DotAccessData\Data;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class ACFProvider {

	public static $folder = BASE_URI . '/config/acf-json';

	private $config;


	/**
	 * Add settings to acf
	 */
	public function addSettings()
	{
		$acf_settings = $this->config->get('acf', []);

		foreach ($acf_settings as $name=>$value)
			acf_update_setting($name, $value);
	}

	public function adminInit(){

		if( !current_user_can('administrator') )
			return;

		if( isset($_GET['clear_acf_meta']) )
			$this->deleteUnusedMeta();

		// Remove generated thumbnails option
		add_settings_field('clean_unused_acf_meta', __('Advanced Custom Fields'), function(){

			$unusedMeta = $this->getUnusedMeta();

			if( $unusedMeta )
				echo '<a class="button button-primary" href="'.get_admin_url().'?clear_acf_meta" title="Be carefull, fields must be synchronised">'.__('Remove').' '.$unusedMeta.' unused meta</a>';
			else
				echo __('Nothing to remove');

		}, 'general');
	}
	
	public function deleteUnusedMeta(){

		global $wpdb;

		$deleteSql = "DELETE FROM `{$wpdb->prefix}postmeta` 
	    WHERE `meta_key` IN 
		( SELECT TRIM(LEADING '_' FROM `meta_key`) AS mk 
			FROM (SELECT * FROM {$wpdb->prefix}postmeta) as pm
			WHERE pm.`meta_value` regexp '^field_[0-9a-f]+' 
				AND pm.`meta_value` NOT IN 
					(SELECT `post_name` FROM `{$wpdb->prefix}posts` WHERE `post_type` = 'acf-field') 
		) 
		OR `meta_key` IN 
		( SELECT `meta_key` AS mk 
			FROM (SELECT * FROM {$wpdb->prefix}postmeta) as pm 
			WHERE pm.`meta_value` regexp '^field_[0-9a-f]+' 
				AND pm.`meta_value` NOT IN 
					(SELECT `post_name` FROM `{$wpdb->prefix}posts` WHERE `post_type` = 'acf-field') 
		)";

		$wpdb->query($deleteSql);

		wp_redirect( get_admin_url(null, 'options-general.php') );
		exit;
	}

	public function getUnusedMeta(){

		global $wpdb;

		$selectSql = "SELECT count(`meta_id`) FROM `{$wpdb->prefix}postmeta` 
	    WHERE `meta_key` IN 
		( SELECT TRIM(LEADING '_' FROM `meta_key`) AS mk 
			FROM `{$wpdb->prefix}postmeta` 
			WHERE `meta_value` regexp '^field_[0-9a-f]+' 
				AND `meta_value` NOT IN 
					(SELECT `post_name` FROM `{$wpdb->prefix}posts` WHERE `post_type` = 'acf-field') 
		) 
		OR `meta_key` IN 
		( SELECT `meta_key` AS mk 
			FROM `{$wpdb->prefix}postmeta` 
			WHERE `meta_value` regexp '^field_[0-9a-f]+' 
				AND `meta_value` NOT IN 
					(SELECT `post_name` FROM `{$wpdb->prefix}posts` WHERE `post_type` = 'acf-field') 
		)";

		return $wpdb->get_var($selectSql);
	}

	
	/**
	 * ACFPlugin constructor.
	 * @param Data $config
	 */
	public function __construct($config)
	{
		$this->config = $config;

		add_filter('acf/settings/save_json', function(){ return $this::$folder; });
		add_filter('acf/settings/load_json', function(){ return [$this::$folder]; });

		// When viewing admin
		if( is_admin() )
		{
			// Setup ACF Settings
			add_action( 'acf/init', [$this, 'addSettings'] );
			add_action( 'admin_init', [$this, 'adminInit'] );
		}
	}
}
