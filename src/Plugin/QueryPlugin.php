<?php

namespace Metabolism\WordpressBundle\Plugin;

/**
 * Class
 */
class QueryPlugin {


    /**
     * @param $query
     * @return void
     */
    public function parse_query($query ) {

        if( $query->is_main_query() && ($query->is_tax() || $query->is_single()) && !$query->is_404 ){

            if( !$query->get_queried_object_id() )
                $query->set_404();
        }
    }


    /**
     * UrlPlugin constructor.
     */
    public function __construct(){

        add_action( 'parse_query', [$this, 'parse_query']);
    }
}
