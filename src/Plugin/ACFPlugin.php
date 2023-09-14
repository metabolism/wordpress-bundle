<?php

namespace Metabolism\WordpressBundle\Plugin;

use Metabolism\WordpressBundle\Entity\Block;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class ACFPlugin {


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
     * Render block
     * @param $acf_block
     * @return void
     */
    public static function renderBlock($acf_block){

        $block = [
            'blockName'=>$acf_block['name'],
            'attrs' => $acf_block
        ];

        if( $image = $acf_block['data']['_preview_image']??false ){

            echo '<img src="'.get_home_url().'/'.$image.'" style="width:100%;height:auto" class="preview_image"/>';
            return;
        }

        $block = new Block($block);

        echo $block->render();
    }


    /**
     * ACFPlugin constructor.
     */
    public function __construct()
    {
        add_filter('acf/validate_field', [$this, 'validateField']);
		add_filter('block_render_callback', [$this, 'renderBlock']);
    }
}
