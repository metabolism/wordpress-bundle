<?php

namespace Metabolism\WordpressBundle\Repository;

use Metabolism\WordpressBundle\Entity\User;
use Metabolism\WordpressBundle\Factory\Factory;

class UserRepository
{
    /**
     * @param $id
     * @return bool|User|null
     */
    public function find($id)
    {
        $user = Factory::create($id, 'user');

        if( !is_wp_error($user) )
            return $user;

        return null;
    }


    /**
     * @return bool|User|null
     * @throws \Exception
     */
    public function findQueried()
    {
        if( is_author() ){

            global $wp_query;
            
            if( !$id = $wp_query->query_vars['author'] )
                throw new \Exception('Author not found', 404);

            return $this->find($id);
        }

        return null;
    }


    /**
     *
     * @return User[]
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
     * @return User[]
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
     * @return User|null
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        $users = $this->findBy($criteria, $orderBy, 1);

        return $users[0]??null;
    }
}