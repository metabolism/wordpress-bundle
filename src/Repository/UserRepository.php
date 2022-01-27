<?php

namespace Metabolism\WordpressBundle\Repository;

use Metabolism\WordpressBundle\Entity\Post;
use Metabolism\WordpressBundle\Factory\Factory;
use Metabolism\WordpressBundle\Factory\PostFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserRepository
{
    /**
     * @param $id
     * @return bool|Post|null
     */
    public function find($id)
    {
        $user = Factory::create($id, 'user');

        if( !is_wp_error($user) )
            return $user;

        return null;
    }


    /**
     * @param $id
     * @return bool|Post|null
     */
    public function findQueried()
    {
        if( is_author() ){

            global $wp_query;
            
            if( !$id = $wp_query->query_vars['author'] )
                throw new NotFoundHttpException();

            return $this->find($id);
        }

        return null;
    }


    /**
     *
     * @return Post[]
     */
    public function findAll(array $orderBy = null)
    {
        return $this->findBy([], $orderBy, -1);
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
        $criteria['fields'] = 'ID';

        if( $limit || !isset($criteria['number']) )
            $criteria['number'] = $limit?:get_option( 'posts_per_page' );

        if( $offset )
            $criteria['offset'] = $offset;

        if( $orderBy )
            $criteria = ['orderby' => $orderBy[0], 'order' => $orderBy[1]??'DESC'];

        $query = new \WP_User_Query($criteria);
        $users = [];

        foreach ($query->get_results() as $user)
            $users[] = Factory::create( $user, 'user' );

        return array_filter($users);
    }


    /**
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria)
    {
        $criteria['fields'] = 'ID';

        $query = new \WP_User_Query($criteria);

        return $query->get_total();
    }


    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @return Post|null
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        $users = $this->findBy($criteria, $orderBy, 1);

        return $users[0]??null;
    }
}