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
    public $url;

	private $_user = null;

    public function __toString()
    {
        return $this->display_name;
    }

	/**
	 * User constructor.
	 *
	 * @param $id
	 * @param array $args
	 */
	public function __construct($id, $args = [])
	{
		if( $user = $this->get($id) ) {

			$this->import($user->data, false, 'user_');

            $user_meta = get_user_meta( $id );

            $this->ID = $id;

            $this->firstname = $user_meta['first_name'][0]??'';
			$this->lastname = $user_meta['last_name'][0]??'';
			$this->description = $user_meta['description'][0]??'';
			$this->link = get_author_posts_url($id);

			if( !isset($args['depth']) || $args['depth'] )
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


    /**
     * Get avatar url
     *
     * @param array $args
     * @return string
     */
	public function getAvatar($args = []){

        $args = get_avatar_data( $this->ID, $args );
        return $args['url'];
    }
}
