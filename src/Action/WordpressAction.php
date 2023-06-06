<?php

namespace Metabolism\WordpressBundle\Action;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class WordpressAction {

    public function init(){}


	public function __construct()
	{
		if( defined('WP_INSTALLING') && WP_INSTALLING )
			return;

        add_action( 'kernel_loaded', [$this, 'init'], 99);
	}
}
