<?php

namespace Metabolism\WordpressBundle\Plugin;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class ACFPlugin {

	public static $acf_folder;

	public function __construct($config)
	{
		self::$acf_folder = BASE_URI . '/config/acf-json';

		add_filter('acf/settings/save_json', function(){ return $this::$acf_folder; });
		add_filter('acf/settings/load_json', function(){ return [$this::$acf_folder]; });

	}
}
