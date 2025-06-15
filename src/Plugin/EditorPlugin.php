<?php

namespace Metabolism\WordpressBundle\Plugin;


/**
 * Class
 */
class EditorPlugin {

    /**
     * Update theme and stylesheet
     */
    public function checkTheme()
    {
        $template = get_option('template');

        if( !is_dir(WP_CONTENT_DIR.'/themes/'.$template) && $template != 'void'){

            update_option('template', 'void');
            update_option('stylesheet', 'void');
        }

        add_action('admin_menu', function (){
            remove_submenu_page('themes.php', 'themes.php' );
        });
    }


    /**
     * Constructor.
     */
    public function __construct()
    {
        // Global init action
        add_action( 'init', [$this, 'checkTheme']);

        // When viewing admin
        if( is_admin() ){

	        add_filter('update_right_now_text', function (){ return 'WordPress %1$s'; });
        }
    }
}
