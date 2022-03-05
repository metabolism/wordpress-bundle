<?php

namespace Metabolism\WordpressBundle\Service;

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

        if( $args['add_home']??true )
            $breadcrumb[] = ['title' => __('Home'), 'link' => home_url('/')];

        if( ($args['data']??false) && is_array($args['data']) )
            $breadcrumb = array_merge($breadcrumb, $args['data']);

        if( $args['add_current']??true ){

            if( (is_single() || is_page()) && !is_attachment() )
            {
                /** @var \WP_Post $post */
                if( $post = get_post() ){

                    if( $post->post_parent ){

                        $parents_id = get_post_ancestors($post->ID);
                        $parents_id = array_reverse($parents_id);

                        foreach ($parents_id as $parent_id)
                            $breadcrumb[] = ['title' => get_the_title($parent_id), 'link' => get_permalink($parent_id)];
                    }

                    $breadcrumb[] = ['title' => $post->post_title];
                }
            }
            elseif( is_archive() )
            {
                if( $title = single_term_title('', false) )
                    $breadcrumb[] = ['title' => $title];
            }
        }

        return $breadcrumb;
    }
}
