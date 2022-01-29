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
        if( is_archive() ){

            if( !$id = get_queried_object_id() )
                throw new \Exception('Term not found', 404);

            return $this->find($id);
        }

        return null;
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

        $criteria['number'] = $limit?:get_option( 'posts_per_page' );

        if( $offset )
            $criteria['offset'] = $offset;

        if( $orderBy )
            $criteria = ['orderby' => $orderBy[0], 'order' => $orderBy[1]??'DESC'];

        $_terms = get_terms( $criteria );
        $terms = [];

        foreach ($_terms as $term)
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
        $terms = $this->findBy($criteria, $orderBy, 1);

        return $terms[0]??null;
    }

    /**
     * @param Term[] $raw_terms
     * @return array
     */
    public function sortHierarchically($raw_terms){

        $terms = [];

        if( is_object($raw_terms) )
            $raw_terms = (Array)$raw_terms;

        $this->sort($raw_terms, $terms);

        return $terms;
    }

    /**
     * @param $terms
     * @param $into
     * @param int $parentId
     */
    public function sort(&$terms, &$into, $parentId = 0)
    {
        foreach ($terms as $i => $term)
        {
            if (!is_wp_error($term) && $term->parent == $parentId)
            {
                $into[$term->ID] = $term;
                unset($terms[$i]);
            }
        }

        foreach ($into as $top_term)
        {
            $top_term->children = [];
            $this->sort($terms, $top_term->children, $top_term->ID);

            if( empty($top_term->children) )
                unset($top_term->children);
        }
    }
}