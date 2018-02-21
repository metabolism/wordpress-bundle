<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */


namespace Metabolism\WordpressLoader\Traits;

/**
 * Class SingletonTrait
 *
 * @package Metabolism\WordpressLoader\Application
 */
trait SingletonTrait {

    /**
     * Instance
     *
     * @var self
     */
    protected static $_instance;


    /**
     * Constructor
     */
    protected function __construct() { }

    /**
     * Get instance
     *
     * @return self
     */
    public static function getInstance()
    {

        $numargs = func_num_args();

        if ( null === static::$_instance ) {

            if ( $numargs == 1 ) {
                static::$_instance = new static( func_get_arg( 0 ) );
            }
            elseif ( $numargs == 2 ) {
                static::$_instance = new static( func_get_arg( 1 ) );
            }
            else {
                static::$_instance = new static();
            }
        }

        return static::$_instance;
    }
}
