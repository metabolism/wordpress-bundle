<?php

namespace Metabolism\WordpressBundle\Entity;

/**
 * Class User
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class User extends Entity
{
	public $login;
	public $nicename;
	public $email;
	public $url;
	public $registered;
	public $status;
	public $display_name;

	private $pass;
	private $_user = null;

	/**
	 * User constructor.
	 *
	 * @param $id
	 */
	public function __construct($id)
	{
		if( $user = $this->get($id) ) {

			$this->import($user->data, false, 'user_');
			$this->addCustomFields('user_'.$id);
		}
	}

	/**
	 * Get user
	 *
	 * @param $pid
	 * @return bool|\WP_User
	 */
	protected function get( $pid ) {

		if( $user = get_userdata($pid) ){

			if( is_wp_error($user) )
				return false;

			$this->_user = $user;
		}

		return $user;
	}

}
