<?php

namespace Metabolism\WordpressBundle\Factory;

class PostFactory {

	public static function create($id=null, $post_type = false){

		if( is_array($id) ) {

			if( isset($id['ID']) )
				$id = $id['ID'];
			else
				return new \WP_Error('post_factory_invalid_post_array', 'The array must contain an ID');

			if(  isset($id['post_type']))
				$post_type = $id['post_type'];
		}
		if( is_object($id) ) {

			if( $post instanceof \WP_Post ) {

				$id = $id->ID;
				$post_type = $id->post_type;
			}
			else{
				return new \WP_Error('post_factory_invalid_post_object', 'The object is not an instance of WP_Post');
			}
		}
		elseif( is_string($id) ) {

			$id = intval($id);

			if( !$id )
				return new \WP_Error('post_factory_invalid_post_id', 'The id is not valid');
		}

		if( !$post_type )
			$post_type = get_post_type($id);

		if( !$post_type )
			return new \WP_Error('post_factory_invalid_post_type', 'Unable to get post type');

		return Factory::create($id, $post_type, 'post');
	}
}
