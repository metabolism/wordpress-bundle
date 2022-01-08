<?php

namespace Metabolism\WordpressBundle\Plugin;


use Metabolism\WordpressBundle\Traits\SingletonTrait;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class PerformancePlugin
{
    public static $plugins = [
        'classic-editor'=>'classic-editor/classic-editor.php',
        'acf-flexible-layouts-manager'=>'acf-flexible-layouts-manager/acf-flexible-layouts-manager.php',
        'acf-restrict-color-picker'=>'acf-restrict-color-picker/acf-restrict-color-picker.php',
        'multisite-clone-duplicator'=>'multisite-clone-duplicator/multisite-clone-duplicator.php'
    ];

    use SingletonTrait;

    public function __construct()
    {
        if ( !is_admin() ){

            add_filter('option_active_plugins', [$this, 'disablePlugins']);
            add_filter('site_option_active_sitewide_plugins', [$this, 'disableSitewidePlugins']);

            remove_action( 'init', 'check_theme_switched', 99 );

            add_action('wp_footer', function (){

                if( WP_DEBUG )
                    echo '<!-- '.get_num_queries().' queries in '.timer_stop(0).' seconds. -->'.PHP_EOL;
            });
        }
    }

    function disablePlugins($plugins){

        $disabled_plugins = array_values(self::$plugins);

        foreach ($plugins as $i => $plugin){

            if ( in_array(($plugin), $disabled_plugins) )
                unset($plugins[$i]);
        }

        return $plugins;
    }

    function disableSitewidePlugins($plugins){

        $disabled_plugins = array_values(self::$plugins);

        foreach ($plugins as $plugin => $time){

            if ( in_array($plugin, $disabled_plugins) )
                unset($plugins[$plugin]);
        }

        return $plugins;
    }
}