<?php

namespace Metabolism\WordpressLoader\Helper;

use Metabolism\WordpressLoader\Traits\TemplateEngineTrait as TemplateEngine;

use Twig_SimpleFilter as SimpleFilter;
use Twig_SimpleFunction as SimpleFunction;

class TwigHelper extends \Twig_Extension {

    use TemplateEngine {
        TemplateEngine::__construct as private __tetConstruct;
    }

    public function __construct( $base_path )
    {
        $this->__tetConstruct( $base_path );
    }

    /**
     * Twig filters
     *
     * @example : {{myval|filer}}
     * @return array
     */
    public function getFilters()
    {

        return [

            new SimpleFilter( "protect_email", [$this,'protect_email'], [
                'pre_escape' => 'html',
                'is_safe'    => ['html']
            ] ),
            new SimpleFilter( "youtube_id", [$this,'youtube_id'] ),
            new SimpleFilter( "clean_id", [$this,'clean_id'] ),
            new SimpleFilter( "ll_CC", [$this,'ll_CC'] ),
            new SimpleFilter( "br_to_space", [$this,'br_to_space'] ),
            new SimpleFilter( "clean_space", [$this,'clean_space'] ),
            new SimpleFilter( "remove_accent", [$this,'remove_accent'] ),
            new SimpleFilter( "typeOf", [$this,'typeOf'] ),
            new SimpleFilter( "bind", [$this,'bind'] ),
            new SimpleFilter( "more", [$this,'more'] ),
            new SimpleFilter( "implode", [$this,'implode'] ),
            new SimpleFilter( "width", [$this,'get_width'] ),
            new SimpleFilter( "height", [$this,'get_height'] ),
            new SimpleFilter( "striptag", [$this,'striptag'] )
        ];
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [

            new SimpleFunction( "upload_url", [$this,'upload_url'] ),
            new SimpleFunction( "asset_url", [$this,'asset_url'] ),
            new SimpleFunction( "GT", [$this,'GT'] ),
            new SimpleFunction( "GTE", [$this,'GTE'] ),
            new SimpleFunction( "LT", [$this,'LT'] ),
            new SimpleFunction( "LTE", [$this,'LTE'] ),
            new SimpleFunction( "blank", [$this,'blank'] ),
            new SimpleFunction( "sizer", [$this,'sizer'], ['is_safe' => ['html']] ),
            new SimpleFunction( "adjacent_key", [$this,'adjacent_key'] ),
            new SimpleFunction( "translate", [$this,'translate'] )
        ];
    }

}
