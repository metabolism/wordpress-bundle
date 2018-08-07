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
class User extends Entity
{
	/**
	 * Post constructor.
	 *
	 * @param null $id
	 */
	public function __construct($id)
	{
		if( $user = $this->get($id) )
			$this->import($user);
	}


	private function get( $pid ) {

		$user = false;

		if( is_int($pid) && $user = get_user_by('id', $pid) )
		{
			if( !$user || is_wp_error($user) )
				return false;
		}

		return $user;
	}

}
