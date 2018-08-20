<?php

namespace Metabolism\WordpressBundle\Entity;

/**
 * Class Term
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Term extends Entity
{
	/**
	 * Post constructor.
	 *
	 * @param null $id
	 */
	public function __construct($id)
	{
		if( is_array($id) )
		{
			if( empty($id) || isset($id['invalid_taxonomy']) )
				return false;

			$id = $id[0];
		}

		if( $term = $this->get($id) )
		{
			$this->import($term);

			if( $term->taxonomy )
				$this->addCustomFields($term->taxonomy.'_'.$id);
		}
	}


	private function get( $pid ) {

		$term = false;

		if( is_int($pid) && $term = get_term($pid) )
		{
			if( !$term || is_wp_error($term) )
				return false;
			
			$term->excerpt = strip_tags(term_description($pid),'<b><i><strong><em><br>');
			$term->link = get_term_link($pid);
			$term->ID = $term->term_id;
			$term->current = get_queried_object_id() == $pid;
		}

		return $term;
	}

}
