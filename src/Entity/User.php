<?php

namespace Metabolism\WordpressBundle\Entity;

/**
 * Class User
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class User extends Entity
{
	public $entity = 'user';

    public $firstname;
    public $lastname;
    public $description;
    public $display_name;
    public $email;
	public $login;
	public $nicename;
	public $registered;
	public $status;

    protected $link;
    protected $avatar;

	/** @var \WP_User */
    private $user;

    public function __toString()
    {
        return $this->display_name??'Invalid user';
    }

	/**
	 * User constructor.
	 *
	 * @param $id
	 */
	public function __construct($id)
	{
		if( $user = $this->get($id) ) {

            $this->ID = $user->ID;
            $this->firstname = $user->first_name;
            $this->lastname = $user->last_name;
            $this->description = $user->description;
            $this->display_name = $user->display_name;
            $this->email = $user->user_email;
            $this->login = $user->user_login;
            $this->nicename = $user->user_nicename;
            $this->registered = $user->user_registered;
            $this->status = $user->user_status;

			$this->loadMetafields($id, 'user');
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

			if( is_wp_error($user) || !$user )
				return false;

			$this->user = $user;
		}

		return $user;
	}


    /**
     * Get avatar url
     *
     * @param array $args
     * @return string
     */
	public function getAvatar($args = []){

        if( is_null($this->avatar)){

            $args = get_avatar_data( $this->ID, $args );
            $this->avatar = $args['url'];
        }

        return $this->avatar;
    }


    /**
     * Get author url
     *
     * @return string
     */
	public function getLink(){

        if( is_null($this->link))
	        $this->link = get_author_posts_url($this->ID);

        return $this->link;
    }
}
