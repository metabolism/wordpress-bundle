<?php

namespace Metabolism\WordpressBundle\Entity;

/**
 * Class User
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


	protected function get( $pid ) {

		$user = false;

		if( is_int($pid) && $user = get_user_by('id', $pid) )
		{
			if( !$user || is_wp_error($user) )
				return false;
		}

		return $user;
	}

}
