<?php

namespace Metabolism\WordpressBundle\Repository;

use Metabolism\WordpressBundle\Entity\Term;
use Metabolism\WordpressBundle\Entity\TermCollection;
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
    public function findQueried($allowNull=false)
    {
        try {

            if( is_404() )
                throw new \Exception('Term not found', 404);

            if( is_archive() ){

                if( !$id = get_queried_object_id() )
                    throw new \Exception('Term not found', 404);

                if( $term = $this->find($id) )
                    return $term;
            }

            throw new \Exception('Term not found', 404);
        }
        catch (\Exception $e){

            if( !$allowNull )
                throw $e;

            return null;
        }
    }


    /**
     * @param array $args
     * @param string $output
     * @param string $operator
     * @return string[]|\WP_Taxonomy[]
     */
    public function findTaxonomies($args=[], $output='names', $operator='and'){

        return get_taxonomies($args, $output, $operator);
    }


    /**
     * @return TermCollection
     */
    public function findAll(array $orderBy = null, $public=true)
    {
        $taxonomies = $this->findTaxonomies(['public'=> $public]);

        $criteria = [
            'taxonomy' => $taxonomies
        ];

        return $this->findBy($criteria, $orderBy, -1);
    }


    /**
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria)
    {
        $criteria['fields'] = 'count';

        $count = new \WP_Term_Query($criteria);

        return intval($count);
    }


    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param $limit
     * @param $offset
     *
     * @see https://developer.wordpress.org/reference/classes/wp_term_query/__construct/
     *
     * @return TermCollection|array
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        if( $limit && $limit > 0 )
            $criteria['number'] = $limit;

        if( $offset )
            $criteria['offset'] = $offset;

        if( $orderBy )
            $criteria = ['orderby' => $orderBy[0], 'order' => $orderBy[1]??'DESC'];

        $collection = new TermCollection($criteria);

        if( isset($criteria['fields']) )
            return $collection->getItems();

        return $collection;
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
