<?php

namespace Metabolism\WordpressBundle\Factory;

class PostFactory {

	/**
	 * Create entity from post_type
	 * @param null $id
	 * @param bool $post_type
	 * @param array $args
	 * @return bool|mixed|\WP_Error
	 */
	public static function create($id=null, $post_type = false, $args = []){

		if( is_array($id) ) {

			if( isset($id['ID']) )
				$id = $id['ID'];
			else
				return new \WP_Error('post_factory_invalid_post_array', 'The array must contain an ID');

			if(  isset($id['post_type']))
				$post_type = $id['post_type'];
		}
		if( is_object($id) ) {

			if( $id instanceof \WP_Post ) {

				$post = $id;
				$id = $post->ID;
				$post_type = $post->post_type;
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

		$post_status = get_post_status( $id );

		if( $post_status && $post_status == 'private' && (!is_user_logged_in() || current_user_can( 'read_private_posts' )) )
			return false;
		elseif( $post_status && $post_status != 'publish' )
			return false;

		return Factory::create($id, $post_type, 'post', $args);
	}
}
