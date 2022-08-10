<?php

namespace Metabolism\WordpressBundle\Service;

use Metabolism\WordpressBundle\Entity\Blog;
use Metabolism\WordpressBundle\Factory\PostFactory;
use Metabolism\WordpressBundle\Factory\TermFactory;

class BreadcrumbService
{
    /**
     * Retrieve paginated links for archive post pages.
     * @param array $args
     * @return array
     */
    public function build($args=[])
    {
        $breadcrumb = [];
		$blog = Blog::getInstance();

        if( $args['add_home']??true )
            $breadcrumb[] = ['title' => __('Home'), 'link' => $blog->getHomeUrl()];

        if( ($args['data']??false) && is_array($args['data']) )
            $breadcrumb = array_merge($breadcrumb, $args['data']);

        if( !($args['data']??false) && !($args['add_home']??false) && !($args['add_current']??false) && is_array($args) )
            $breadcrumb = array_merge($breadcrumb, $args);

        if( $args['add_current']??true ){

			$queried_object = $blog->getQueriedObject();

            if( $blog->isSingle() )
            {
                if( $post = PostFactory::create( $queried_object ) ){

                    if( $post->getParent() ){

	                    $parents = $post->getAncestors();

                        foreach ($parents as $parent)
                            $breadcrumb[] = ['title' => $parent->getTitle(), 'link' => $parent->getLink()];
                    }

                    $breadcrumb[] = ['title' => $post->getTitle()];
                }
            }
            elseif( $blog->isTax() )
            {
	            if( $term = TermFactory::create( $queried_object ) )
                    $breadcrumb[] = ['title' => $term->getTitle()];
            }
        }

        return $breadcrumb;
    }
}
