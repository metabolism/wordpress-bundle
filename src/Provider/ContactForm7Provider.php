<?php

namespace Metabolism\WordpressBundle\Provider;

use Dflydev\DotAccessData\Data;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class ContactForm7Provider {

    public static function enqueue_scripts(){

        add_action( 'wp_enqueue_scripts', function (){

            if ( function_exists( 'wpcf7_enqueue_scripts' ) )
                wpcf7_enqueue_scripts();

            if ( function_exists( 'wpcf7_enqueue_styles' ) )
                wpcf7_enqueue_styles();
        });
    }

    /**
     * constructor.
     * @param Data $config
     */
    public function __construct($config)
    {
        add_filter( 'wpcf7_load_js', '__return_false' );
        add_filter( 'wpcf7_load_css', '__return_false' );
    }
}
