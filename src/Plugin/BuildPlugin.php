<?php

// bad design but required by to make wp style function
namespace Metabolism\WordpressBundle\Plugin;

use Dflydev\DotAccessData\Data;
use Metabolism\WordpressBundle\Traits\SingletonTrait;
use function Env\env;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class BuildPlugin {

    use SingletonTrait;

    private $config;

    /**
     * Add maintenance button and checkbox
     */
    public function addBuildButton()
    {
        if( !current_user_can('editor') && !current_user_can('administrator') )
            return;

        add_action( 'admin_bar_menu', function( $wp_admin_bar )
        {
            $args = [
                'id'    => 'build',
                'title' => '<span class="ab-icon"></span>'.__('Build'),
                'href'  => env('BUILD_HOOK')
            ];

            $wp_admin_bar->add_node( $args );

        }, 999 );
    }

    /**
     * MaintenancePlugin constructor.
     * @param Data $config
     */
    public function __construct($config)
    {
        $this->config = $config;

        if( is_admin() && env('BUILD_HOOK') )
            add_action( 'init', [$this, 'addBuildButton']);
    }
}