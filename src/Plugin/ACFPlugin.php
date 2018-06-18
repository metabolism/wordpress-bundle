<?php

namespace Metabolism\WordpressBundle\Plugin;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class ACFPlugin {

	public static $acf_folder;
	private $config;

	public function __construct($config)
	{
		$this->config = $config;

		self::$acf_folder = BASE_URI . '/config/acf-json';

		add_filter('acf/settings/save_json', function(){ return $this::$acf_folder; });
		add_filter('acf/settings/load_json', function(){ return [$this::$acf_folder]; });
		
		add_filter('acf/fields/google_map/api', function( $api ){

			$api['key'] = $this->config->get('gmap_api_key');
			return $api;
		});
	}
}
