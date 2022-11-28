<?php

namespace Metabolism\WordpressBundle\Plugin;

use Metabolism\WordpressBundle\WordpressBundle;

/**
 * Class
 */
class PerformancePlugin {

    public function disabledPlugins(){

        if( !is_admin() && !WordpressBundle::isLoginUrl() ){

            add_filter( 'option_active_plugins', function( $plugins ){

                //disable wp-2fa bacause of Symfony class collision
                $myplugin = "wp-2fa/wp-2fa.php";
                $k = array_search( $myplugin, $plugins );
                unset( $plugins[$k] );

                return $plugins;
            });
        }
    }

    /**
     * constructor.
     */
    public function __construct(){

       $this->disabledPlugins();
    }
}
