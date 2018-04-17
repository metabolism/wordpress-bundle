<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Metabolism\WordpressBundle\Entity;

/**
 * Class Term
 * @see \Timber\Term
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

		$term = $this->get($id);
		$this->import($term);
	}


	private function get( $pid ) {

		$term = false;

		if( is_int($pid) && $term = get_term($pid) )
		{
			$term->excerpt = strip_tags(term_description($pid),'<b><i><strong><em><br>');
			$term->link = get_term_link($pid);
		}

		return $term;
	}

}
