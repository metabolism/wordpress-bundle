<?php

namespace Metabolism\WordpressBundle\Repository;


use Metabolism\WordpressBundle\Entity\Post;
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
     * @return Post[]
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
     * @return Post|Post[]|null
     */
    public function findQueried()
    {
        if( is_archive() || (is_home() && get_option('show_on_front') == 'posts') ){

            global $wp_query;
            return $this->findBy($wp_query->query);
        }
        elseif( is_single() || is_page() ){

            if( !$id = get_the_ID() )
                throw new \Exception('Post not found', 404);

            return $this->find($id);
        }

        return null;
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param $limit
     * @param $offset
     * @return Post[]
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $criteria['fields'] = 'ids';

        if( $limit || !isset($criteria['posts_per_page']) )
            $criteria['posts_per_page'] = $limit?:get_option( 'posts_per_page' );

        if( $offset )
            $criteria['offset'] = $offset;

        if( $orderBy )
            $criteria = ['orderby' => $orderBy[0], 'order' => $orderBy[1]??'DESC'];

        $query = new \WP_Query( $criteria );
        $posts = [];

        foreach ($query->posts as $post)
            $posts[] = PostFactory::create( $post );

        return array_filter($posts);
    }


    /**
     * @param array $criteria
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
        if( $post = get_page_by_state($state) )
            return PostFactory::create( $post );

        return null;
    }
}