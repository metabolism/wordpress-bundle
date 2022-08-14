<?php

namespace Metabolism\WordpressBundle\Plugin;

use Metabolism\WordpressBundle\Helper\BlockHelper;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class ACFPlugin {

    public static $folder = '/config/packages/acf';


    /**
     * Add entity return format
     * @param $field
     * @return array
     */
    public function validateField($field){

        if( $field['name'] === 'return_format'){

            if( isset($field['choices']['object'] ) )
                $field['choices']['link'] = __('Link');

            $field['choices']['entity'] = __('Entity');
            $field['default_value'] = 'entity';
        }

        return $field;
    }


    /**
     * ACFPlugin constructor.
     */
    public function __construct()
    {
        add_filter('acf/settings/save_json', function(){ return BASE_URI.$this::$folder; });
        add_filter('acf/settings/load_json', function(){ return [BASE_URI.$this::$folder]; });

        add_filter('acf/validate_field', [$this, 'validateField']);

		add_filter('block_render_callback', function ( $callback ){ return [BlockHelper::class, 'render']; });
    }
}
