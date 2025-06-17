<?php

namespace Metabolism\WordpressBundle\Action;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class LoginAction {

	/**
	 * Init placeholder
	 */
	public function init(){}


	public function __construct()
	{
		if( defined('WP_INSTALLING') && WP_INSTALLING )
			return;

		$this->init();
	}
}
