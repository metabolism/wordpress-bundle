<?php

namespace Metabolism\WordpressBundle\Controller;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class WordpressController {

    protected $config;

    public function init(){}

    /**
     * Load App configuration
     */
    public function loadConfig()
    {
        global $_config;
        $this->config = $_config;
    }

	public function __construct()
	{
		if( defined('WP_INSTALLING') && WP_INSTALLING )
			return;

        add_action( 'init', [$this, 'loadConfig'] );
        add_action( 'init', [$this, 'init']);
	}
}
