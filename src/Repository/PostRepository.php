<?php

namespace Metabolism\WordpressBundle\Repository;

use Metabolism\WordpressBundle\Entity\Post;
use Metabolism\WordpressBundle\Entity\PostCollection;
use Metabolism\WordpressBundle\Factory\PostFactory;

class PostRepository
{
    /**
     * @param $id
     * @return bool|Post|null
     */
    public function find($id)
    {
        $post = PostFactory::create($id);

        if( !is_wp_error($post) )
            return $post;

        return null;
    }


    /**
     *
     * @return PostCollection
     */
    public function findAll(array $orderBy = null)
    {
        $post_types = get_post_types(['public'=> true]);

        $criteria = [
            'post_type' => array_diff($post_types, ['attachment', 'revision', 'nav_menu_item'])
        ];

        return $this->findBy($criteria, $orderBy, -1);
    }


    /**
     *
     * @return PostCollection|Post
     * @throws \Exception
     */
    public function findQueried()
    {
        if( is_404() )
            throw new \Exception('Post not found', 404);

        if( is_archive() || (is_home() && get_option('show_on_front') == 'posts') || is_search() ){

            global $wp_query;
            return $this->findBy($wp_query->query);
        }
        elseif( is_single() || is_page() ){

            if( !$id = get_the_ID() )
                throw new \Exception('Post not found', 404);

            return $this->find($id);
        }

        throw new \Exception('Post not found', 404);
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param $limit
     * @param $offset
     * @return PostCollection
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $criteria['fields'] = 'ids';

        if( $limit )
            $criteria['posts_per_page'] = $limit;

        if( $offset )
            $criteria['offset'] = $offset;

        if( $orderBy )
            $criteria = ['orderby' => $orderBy[0], 'order' => $orderBy[1]??'DESC'];

        return new PostCollection($criteria);
    }


    /**
     * @param array $criteria
     *
     * @return int
     */
    public function count(array $criteria)
    {
        $criteria['fields'] = 'ids';

        $query = new \WP_Query( $criteria );

        return $query->found_posts;
    }


    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @return Post|null
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        $posts = $this->findBy($criteria, $orderBy, 1);

        return $posts[0]??null;
    }


    /**
     * @param $state
     * @return Post|null
     */
    public function findByState($state)
    {
        if( function_exists('get_page_by_state') && $post = get_page_by_state($state) )
            return PostFactory::create( $post );

        return null;
    }


    /**
     * @param $ids
     * @return PostCollection
     */
    public function findByGuid(array $ids)
    {
        $postCollection = new PostCollection();

        if( !count($ids) )
            return $postCollection;

        global $wpdb;
        $in = implode(',', array_fill(0, count($ids), '%s') );

        $ids = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid IN ($in)", $ids ), ARRAY_A );
        $ids = array_map(function ($item){ return $item['ID']; }, $ids);

        $postCollection->setPosts($ids);

        return $postCollection;
    }
}
