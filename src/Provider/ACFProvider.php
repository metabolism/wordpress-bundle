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
		}
	}
}
