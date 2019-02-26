<?php

namespace Metabolism\WordpressBundle\Entity;

/**
 * Class User
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class User extends Entity
{
	public $caps;
	public $cap_key;
	public $roles;
	public $allcaps;
	public $first_name;
	public $last_name;
    public $login;
    public $pass;
    public $nicename;
    public $email;
    public $url;
    public $registered;
    public $status;
    public $display_name;

	/**
	 * User constructor.
	 *
	 * @param $id
	 */
	public function __construct($id)
	{
		if( $user = $this->get($id) ) {
			$this->import($user, false, 'user_');
			$this->addCustomFields($id);
		}
	}

	/**
	 * Get user
	 *
	 * @param $pid
	 * @return bool|\WP_User
	 */
	protected function get( $pid ) {

		if( is_int($pid) && $user = get_userdata($pid) )
			return $user;

		return false;
	}

}
