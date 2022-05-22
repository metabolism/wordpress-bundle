<?php

namespace Metabolism\WordpressBundle\Repository;

use Metabolism\WordpressBundle\Entity\Term;
use Metabolism\WordpressBundle\Factory\TermFactory;

class TermRepository
{
    /**
     * @param $id
     * @return bool|Term|null
     */
    public function find($id)
    {
        $term = TermFactory::create($id);

        if( !is_wp_error($term) )
            return $term;

        return null;
    }

    /**
     *
     * @return bool|Term|null
     * @throws \Exception
     */
    public function findQueried()
    {
        if( is_404() )
            throw new \Exception('Term not found', 404);

        if( is_archive() ){

            if( !$id = get_queried_object_id() )
                throw new \Exception('Term not found', 404);

            return $this->find($id);
        }

        throw new \Exception('Term not found', 404);
    }

    /**
     * @return Term[]
     */
    public function findAll(array $orderBy = null)
    {
        $criteria = [
            'taxonomy' => get_taxonomies(['public'=> true])
        ];

        return $this->findBy($criteria, $orderBy, -1);
    }


    /**
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria)
    {
        $criteria['fields'] = 'ID';
        $criteria['number'] = 0;

        $query = new \WP_Term_Query($criteria);

        return count($query->terms);
    }


    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param $limit
     * @param $offset
     *
     * @see https://developer.wordpress.org/reference/classes/wp_term_query/__construct/
     *
     * @return Term[]
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $criteria['fields'] = 'ids';

        $criteria['number'] = $limit?:0;

        if( $offset )
            $criteria['offset'] = $offset;

        if( $orderBy )
            $criteria = ['orderby' => $orderBy[0], 'order' => $orderBy[1]??'DESC'];

        $query = new \WP_Term_Query($criteria);
        $terms = [];

        foreach ($query->terms as $term)
            $terms[] = TermFactory::create( $term );

        return array_filter($terms);
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @return Term
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        $criteria['sort'] = false;

        $terms = $this->findBy($criteria, $orderBy, 1);

        return $terms[0]??null;
    }
}
