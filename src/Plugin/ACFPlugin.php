<?php

namespace Metabolism\WordpressBundle\Plugin;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class ACFPlugin {

	public static $acf_folder;
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

	
	public function __construct($config)
	{
		$this->config = $config;

		self::$acf_folder = BASE_URI . '/config/acf-json';

		add_filter('acf/settings/save_json', function(){ return $this::$acf_folder; });
		add_filter('acf/settings/load_json', function(){ return [$this::$acf_folder]; });

		// When viewing admin
		if( is_admin() )
		{
			// Setup ACF Settings
			add_action( 'acf/init', [$this, 'addSettings'] );
		}
	}
}
