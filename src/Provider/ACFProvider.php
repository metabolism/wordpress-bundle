<?php

namespace Metabolism\WordpressBundle\Provider;

use Dflydev\DotAccessData\Data;
use function Env\env;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class ACFProvider {

	public static $folder = '/config/packages/acf';
    public static $folder_legacy = '/config/acf-json';

	private $config;


	/**
	 * Add settings to acf
	 */
	public function addSettings()
	{
		$acf_settings = $this->config->get('acf.settings', []);

		foreach ($acf_settings as $name=>$value)
			acf_update_setting($name, $value);

		if( $google_api_key = env('GOOGLE_MAP_API_KEY') )
            acf_update_setting('google_api_key', $google_api_key);
	}


	/**
	 * Add wordpress configuration 'options_page' fields as ACFHelper Options pages
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
	 * @return array
	 */
	public function editToolbars($toolbars){

		$custom_toolbars = $this->config->get('acf.toolbars', false);

		return $custom_toolbars ?: $toolbars;
	}


	/**
	 * Add theme to field selection
	 * @param $field
	 * @return array
     */
	public function addTaxonomyTemplates($field){

		if( $field['type'] == 'select' && $field['_name'] == 'taxonomy'){

			$types = $this->config->get('template.taxonomy', []);
			$all_templates = [];

			foreach ($types as $type=>$templates){
				foreach ($templates as $key=>$name){
					$all_templates['template_'.$type.':'.$key] = ucfirst(str_replace('_', ' ', $type)).' : '.$name;
				}
			}
			$field['choices'][__('Template').' (template)'] = $all_templates;
		}

		return $field;
	}

	/**
	 * Disable database query for non editable field
	 * @param $unused
	 * @param $post_id
	 * @param $field
	 * @return string|null
	 */
	public function preLoadValue($unused, $post_id, $field){

		if( $field['type'] == 'message' || $field['type'] == 'tab' )
			return '';

		return null;
	}


	/**
	 * Filter preview sizes
	 * @param $sizes
	 * @return array
	 */
	public function getImageSizes($sizes){

		return ['thumbnail'=>$sizes['thumbnail'], 'full'=>$sizes['full']];
	}


	/**
	 * Add entity return format
	 * @param $field
	 * @return array
	 */
	public function validateField($field){

        if( $field['name'] == 'return_format'){

	        if( isset($field['choices']['object'] ) )
		        $field['choices']['link'] = __('Link');

            $field['choices']['entity'] = __('Entity');
            $field['default_value'] = 'entity';
        }

		return $field;
	}


	/**
	 * Change query to replace template by term slug
	 * @param $args
	 * @param $field
	 * @param $post_id
	 * @return mixed
	 */
	public function filterPostsByTermTemplateMeta($args, $field, $post_id ){

		if( $field['type'] == 'relationship' && isset($field['taxonomy'])){

			foreach ($args['tax_query'] as $id=>&$taxonomy){

				if( is_array($taxonomy) && strpos($taxonomy['taxonomy'], 'template_') === 0){

					$taxonomy['taxonomy'] = str_replace('template_','', $taxonomy['taxonomy']);

					$terms = get_terms($taxonomy['taxonomy']);
					$terms_by_template = [];
					foreach ($terms as $term){
						$template = get_term_meta($term->term_id, 'template');
						if(!empty($template) )
							$terms_by_template[$template[0]][] = $term->slug;
					}

					$terms = [];
					foreach ($taxonomy['terms'] as $template){
						if( isset($terms_by_template[$template]) )
							$terms = array_merge($terms, $terms_by_template[$template]);
					}

					$taxonomy['terms'] = $terms;
				}
			}
		}

		return $args;
	}


	/**
	 * ACFPlugin constructor.
	 * @param Data $config
	 */
	public function __construct($config)
	{
		$this->config = $config;
        
        if( !is_dir(BASE_URI.$this::$folder) && is_dir(BASE_URI.$this::$folder_legacy) && $this::$folder != $this::$folder_legacy )
            $this::$folder = $this::$folder_legacy;

		add_filter('acf/settings/save_json', function(){ return BASE_URI.$this::$folder; });
		add_filter('acf/settings/load_json', function(){ return [BASE_URI.$this::$folder]; });
		add_filter('acf/pre_load_value', [$this, 'preLoadValue'], 10, 3);
		add_filter('acf/prepare_field', [$this, 'addTaxonomyTemplates']);
		add_filter('acf/fields/relationship/query/name=items', [$this, 'filterPostsByTermTemplateMeta'], 10, 3);
		add_filter('acf/get_image_sizes', [$this, 'getImageSizes'] );

		if( $this->config->get('acf.settings.use_entity', true) )
            add_filter('acf/validate_field', [$this, 'validateField']);

		// When viewing admin
		if( is_admin() )
		{
			add_filter( 'wp-bundle/admin_notices', function($folders){

				if( class_exists('ACF') ){

					$folders[] = PUBLIC_DIR.'/uploads/acf-thumbnails';
					$folders[] = $this::$folder;
				}

				return $folders;
			});

			// Setup ACFHelper Settings
			add_action( 'acf/init', [$this, 'addSettings'] );
			add_filter( 'acf/fields/wysiwyg/toolbars' , [$this, 'editToolbars']  );
			add_action( 'init', [$this, 'addOptionPages'] );
			add_filter( 'acf/settings/show_admin', function( $show ) {
				return current_user_can('administrator');
			});
		}
	}
}
