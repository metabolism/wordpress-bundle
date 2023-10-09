<?php

namespace Metabolism\WordpressBundle\Repository;

use Metabolism\WordpressBundle\Entity\Comment;
use Metabolism\WordpressBundle\Entity\CommentCollection;
use Metabolism\WordpressBundle\Factory\Factory;

class CommentRepository
{
    /**
     * @param $id
     * @return bool|Comment|null
     */
    public function find($id)
    {
        $comment = Factory::create($id, 'comment');

        if( !is_wp_error($comment) )
            return $comment;

        return null;
    }


    /**
     *
     * @return CommentCollection
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
     * @return CommentCollection
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        if( $limit || !isset($criteria['number']) )
            $criteria['number'] = $limit?:5;

        if( $offset )
            $criteria['offset'] = $offset;

        if( $orderBy )
            $criteria = ['orderby' => $orderBy[0], 'order' => $orderBy[1]??'DESC'];

        return new CommentCollection($criteria);
    }


    /**
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria)
    {
        $criteria['count'] = true;

        $query = new \WP_Comment_Query($criteria);

        return $query->get_comments();
    }


    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @return Comment|null
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        $comments = $this->findBy($criteria, $orderBy, 1);

        return $comments[0]??null;
    }
}
