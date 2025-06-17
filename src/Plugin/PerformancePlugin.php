<?php

namespace Metabolism\WordpressBundle\Plugin;

use Metabolism\WordpressBundle\WordpressBundle;

/**
 * Class
 */
class PerformancePlugin {

    public function disablePlugins(){

        if( !is_admin() && !WordpressBundle::isLoginUrl() ){

            if( is_multisite() )
                add_filter('site_option_active_sitewide_plugins',  [$this, 'disableWP2FA']);

            add_filter('option_active_plugins', [$this, 'disableWP2FA']);
        }
    }

    public function disableWP2FA($plugins){

        //disable wp-2fa because of Symfony class collision
        $wp_2fa = "wp-2fa/wp-2fa.php";

        if( $k = array_search( $wp_2fa, $plugins ) )
            unset( $plugins[$k] );

        if( isset($plugins[$wp_2fa]) )
            unset( $plugins[$wp_2fa] );

        return $plugins;
    }

    /**
     * constructor.
     */
    public function __construct(){

        $this->disablePlugins();
    }
}
