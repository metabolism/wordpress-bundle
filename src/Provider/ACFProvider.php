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
		$acf_settings = $this->config->get('acf.settings');

		//retro compat
		if(!$acf_settings)
			$acf_settings = ['google_api_key'=>$this->config->get('acf.google_api_key')];

		foreach ($acf_settings as $name=>$value)
			acf_update_setting($name, $value);
	}


	/**
	 * Add wordpress configuration 'options_page' fields as ACF Options pages
	 */
	public function addOptionPages()
	{
		if( function_exists('acf_add_options_page') )
		{
			$args = ['autoload' => true, 'page_title' => __('Options', 'acf'), 'menu_slug' => 'acf-options'];

			acf_add_options_page($args);

			$options = $this->config->get('acf.options_page', []);

			//retro compat
			$options = array_merge($options, $this->config->get('options_page', []));

 			foreach ( $options as $args ){

 				if( is_array($args) )
				    $args['autoload'] = true;
 				else
				    $args = ['page_title'=>$args, 'autoload'=>true];

			    acf_add_options_sub_page($args);
		    }
		}
	}


	/**
	 * Customize basic toolbar
	 * @param $toolbars
	 * @return
	 */
	public function editToolbars($toolbars){

		$custom_toolbars = $this->config->get('acf.toolbars');

		return $custom_toolbars ? $custom_toolbars : $toolbars;
	}


	/**
	 * Disable database query for non editable field
	 * @param $unused
	 * @param $post_id
	 * @param $field
	 * @return
	 */
	public function preLoadValue($unused, $post_id, $field){

		if( $field['type'] == 'message' || $field['type'] == 'tab' )
			return '';

		return null;
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
		add_filter('acf/pre_load_value', [$this, 'preLoadValue'], 10, 3);

		// When viewing admin
		if( is_admin() )
		{
			// Setup ACF Settings
			add_action( 'acf/init', [$this, 'addSettings'] );
			add_filter( 'acf/fields/wysiwyg/toolbars' , [$this, 'editToolbars']  );
			add_action( 'init', [$this, 'addOptionPages'] );
		}
	}
}
