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
     * @return array|false
     */
    public function build($args=[])
    {
        $breadcrumb = [];
		$blog = Blog::getInstance();

        if( $blog->isFrontPage() )
            return false;

        if( $args['add_home']??true )
            $breadcrumb[] = ['title' => 'Home', 'link' => $blog->getHomeLink()];

        if( ($args['data']??false) && is_array($args['data']) )
            $breadcrumb = array_merge($breadcrumb, $args['data']);

        if( !($args['data']??false) && !($args['add_home']??false) && !($args['add_current']??false) && is_array($args) )
            $breadcrumb = array_merge($breadcrumb, $args);

        if( $args['add_current']??true ){

			$queried_object = $blog->getQueriedObject();

            if( $blog->isSingle() ) {

                if( $post = PostFactory::create( $queried_object ) ){

                    if( $link = $blog->getArchiveLink( $post->getType() ) )
                        $breadcrumb[] = ['title' => $blog->getArchiveTitle( $post->getType() ), 'link' => $link];

                    if( $post->getParent() ){

	                    $parents = $post->getAncestors();

                        foreach ($parents as $parent)
                            $breadcrumb[] = ['title' => $parent->getTitle(), 'link' => $parent->getLink()];
                    }

                    $breadcrumb[] = ['title' => $post->getTitle(), 'link'=>false];
                }
            }
            elseif( $blog->isTax() ) {

	            if( $term = TermFactory::create( $queried_object ) ){

                    $post_types = $term->getPostTypes();

                    if( count($post_types) == 1 && $link = $blog->getArchiveLink( $post_types[0] ) )
                            $breadcrumb[] = ['title' => $blog->getArchiveTitle( $post_types[0] ), 'link' => $link];

                    $breadcrumb[] = ['title' => $term->getTitle(), 'link'=>false];
                }
            }
            elseif( $post_type = $blog->isArchive() ) {

	            $breadcrumb[] = ['title' => $blog->getArchiveTitle($post_type), 'link'=>false];
            }
        }

        return $breadcrumb;
    }
}
