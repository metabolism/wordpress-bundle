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

        if( substr($block['blockName'], 0, 4) !== 'acf/')
            return $block;

        if( class_exists('ACF') && !empty($block['attrs']) ){

            $block['id'] = acf_ensure_block_id_prefix(acf_get_block_id( $block['attrs'] ));
            $class = substr($block['blockName'], 4).'-block';

            return Factory::create($block, $class, 'block');
        }

        return false;
	}
}
