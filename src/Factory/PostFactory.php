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

		if( is_null($id) || empty($id) || !$id ){

			return false;
		}
		elseif( is_array($id) ) {

			if( isset($id['ID']) )
				$id = $id['ID'];
			else
				return false;

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

				return false;
			}
		}
		elseif( is_string($id) ) {

			$id = intval($id);

			if( !$id )
				return false;
		}

		if( !$post_type )
			$post_type = get_post_type($id);

		if( !$post_type )
			return false;

		$post_status = get_post_status( $id );

		switch($post_status){

			case '':
			case false:
			case 'trash':
			case 'auto-draft':

			if( !in_array($post_status, $args['post_status']??[]) )
				return false;
			break;
			
			case 'private':

				if( (!is_user_logged_in() || !current_user_can( 'read_private_posts' )) && !in_array($post_status, $args['post_status']??[]) )
					return false;
				break;

			case 'draft':
			case 'pending':
			case 'inherit':
			case 'future':

			if( (!is_user_logged_in() || !current_user_can( 'edit_posts' ))  && !in_array($post_status, $args['post_status']??[]) )
				return false;
			break;
		}

		return Factory::create($id, $post_type, 'post', $args);
	}
}
