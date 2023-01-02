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
     * @param array $args
     * @param string $output
     * @param string $operator
     * @return string[]|\WP_Post_Type[]
     */
    public function findPostTypes($args=[], $output='names', $operator='and'){

        return get_post_types($args, $output, $operator);
    }


    /**
     *
     * @return PostCollection
     */
    public function findAll(array $orderBy = null, $public=true)
    {
        $post_types = $this->findPostTypes(['public'=> $public, 'publicly_queryable'=>$public]);

        $criteria = [
            'post_type' => $post_types
        ];

        return $this->findBy($criteria, $orderBy, -1);
    }


    /**
     *
     * @return PostCollection|Post
     * @throws \Exception
     */
    public function findQueried($allowNull=false)
    {
        try {

            if( is_404() )
                throw new \Exception('Post not found', 404);

            if( is_archive() || is_search() || (is_home() && get_option('show_on_front') == 'posts')){

                global $wp_query;
                return new PostCollection($wp_query);
            }
            elseif( $id = get_the_ID() ){

                return $this->find($id);
            }

            throw new \Exception('Post not found', 404);
        }
        catch (\Exception $e){

            if( !$allowNull )
                throw $e;

            return null;
        }
    }


    /**
     * @param array $criteria https://developer.wordpress.org/reference/classes/wp_query/#parameters
     * @param array|string|null $orderBy
     * @param $limit
     * @param $offset
     * @return PostCollection
     */
    public function findBy(array $criteria, $orderBy = null, $limit = null, $offset = null)
    {
        $criteria['fields'] = 'ids';

        if( !isset($criteria['post_status']) )
            $criteria['post_status'] = 'publish';

        if( $limit )
            $criteria['posts_per_page'] = $limit;

        if( $offset )
            $criteria['offset'] = $offset;

        if( $orderBy ){

            if( is_string($orderBy) )
                $criteria = array_merge($criteria, ['orderby' => $orderBy, 'order' => 'DESC']);
            else
                $criteria = array_merge($criteria, ['orderby' => (array_keys($orderBy)[0]), 'order' => (array_values($orderBy)[0])]);
        }

        return new PostCollection($criteria);
    }


    /**
     * @param array $criteria https://developer.wordpress.org/reference/classes/wp_query/#parameters
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
     * @param array $criteria https://developer.wordpress.org/reference/classes/wp_query/#parameters
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
     * @param array $ids
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


    /**
     * @param $id
     * @return Post|null
     */
    public function findOneByGuid($id)
    {
        $postCollection = $this->findByGuid([$id]);

        if( count($postCollection) )
            return $postCollection[0];

        return null;
    }
}
