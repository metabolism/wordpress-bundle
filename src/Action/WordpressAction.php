<?php

namespace Metabolism\WordpressBundle\Action;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class WordpressAction {

    /**
     * Init placeholder
     */
    public function init(){}

    /**
     * Loaded placeholder
     */
    public function loaded(){}


	public function __construct()
	{
		if( defined('WP_INSTALLING') && WP_INSTALLING )
			return;

        add_action( 'init', [$this, 'init'], 99);
        add_action( 'kernel_loaded', [$this, 'loaded'], 99);
	}
}
