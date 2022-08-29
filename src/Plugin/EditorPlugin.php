<?php

namespace Metabolism\WordpressBundle\Plugin;


/**
 * Class
 */
class EditorPlugin {

    public function adminHead(){

        $entrypoints = BASE_URI.PUBLIC_DIR . '/build/entrypoints.json';

        if( file_exists($entrypoints) ){

            $entrypoints = json_decode(file_get_contents($entrypoints), true);

            if( $entrypoints = $entrypoints['entrypoints']['backoffice']??false ){

                foreach ($entrypoints['js']??[] as $file)
                    echo '<script src="'.$file.'"></script>';

                foreach ($entrypoints['css']??[] as $file)
                    echo '<link rel="stylesheet" href="'.$file.'" media="all"/>';
            }

            echo "\n";
        }

		echo "<style>.form-table.permalink-structure, .form-table.permalink-structure+h2{ display:none }</style>";
    }


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
    }


    /**
     * ConfigPlugin constructor.
     */
    public function __construct()
    {
        // Global init action
        add_action( 'init', function()
        {
            $this->checkTheme();
        });

        // When viewing admin
        if( is_admin() ){

	        add_filter('update_right_now_text', function (){ return 'WordPress %1$s'; });
	        add_action('admin_head', [$this, 'adminHead']);
        }
    }
}
