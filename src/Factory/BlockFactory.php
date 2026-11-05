<?php

namespace Metabolism\WordpressBundle\Factory;

use Metabolism\WordpressBundle\Entity\Post;

class BlockFactory {

	/**
	 * Create entity from post_type
	 * @param null $id
	 * @param bool $post_type
	 * @return bool|Post|\WP_Error
	 */
	public static function create($block){

        if( empty($block['blockName']??'') )
            return false;

        $parent_block = null;

        $pre_render = apply_filters( 'pre_render_block', null, $block, $parent_block );

        if ( ! is_null( $pre_render ) )
            return $pre_render;

        $source_block = $block;
        $parsed_block = apply_filters( 'render_block_data', $block, $source_block, $parent_block );

        if( substr($parsed_block['blockName'], 0, 4) !== 'acf/')
            return $parsed_block;

        if( class_exists('ACF') && !empty($parsed_block['attrs']) ){

            $parsed_block['id'] = acf_ensure_block_id_prefix(acf_get_block_id( $parsed_block['attrs'] ));
            $class = substr($parsed_block['blockName'], 4).'-block';

            return Factory::create($parsed_block, $class, 'block');
        }

        return false;
	}
}
