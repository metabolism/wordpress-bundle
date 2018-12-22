<?php

namespace Metabolism\WordpressBundle\Factory;

class TaxonomyFactory {

	public static function create($id=null, $taxonomy_name = false){

		if( is_array($id) ) {

			if( isset($id['term_id']) )
				$id = $id['term_id'];
			else
				return new \WP_Error('taxonomy_factory_invalid_term_array', 'The array must contain a term_id');

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
				return new \WP_Error('taxonomy_factory_invalid_term_object', 'The object is not an instance of WP_Term');
			}
		}
		elseif( is_string($id) ) {

			$id = intval($id);

			if( !$id )
				return new \WP_Error('taxonomy_factory_invalid_term_id', 'The id is not valid');
		}

		if( !$taxonomy_name )
		{
			$term = get_term($id);
			if( $term && !is_wp_error($term) )
				$taxonomy_name = $term->taxonomy;
		}

		if( !$taxonomy_name )
			return new \WP_Error('taxonomy_factory_invalid_taxonomy_name', 'Unable to get taxonomy name');

		return Factory::create($id, $taxonomy_name, 'term');
	}
}
