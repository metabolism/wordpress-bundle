<?php

namespace Metabolism\WordpressBundle\Factory;

class TermFactory {

	/**
	 * Create entity from taxonomy name
	 * @param null $id
	 * @param bool $taxonomy_name
	 * @param array $args
	 * @return bool|mixed|\WP_Error
	 */
	public static function create($id=null, $taxonomy_name = false, $args = []){

		if( is_null($id) || empty($id) ){

			return false;
		}
		elseif( is_array($id) ) {

			if( isset($id['term_id']) )
				$id = $id['term_id'];
			else
				return false;

			if(  isset($id['taxonomy']))
				$taxonomy_name = $id['taxonomy'];
		}
		if( is_object($id) ) {

			if( $id instanceof \WP_Term ) {

				$term = $id;
				$id = $term->term_id;
				$taxonomy_name = $term->taxonomy;
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

		if( !$taxonomy_name )
		{
			$term = get_term($id);
			if( $term && !is_wp_error($term) )
				$taxonomy_name = $term->taxonomy;
		}

		if( !$taxonomy_name )
			return false;

		return Factory::create($id, $taxonomy_name, 'term', $args);
	}
}
