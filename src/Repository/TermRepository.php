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
     * @return TermCollection
     */
    public function findAll(array $orderBy = null, $public=true)
    {
	    if( $public ){

		    $taxonomies = get_taxonomies(['public'=> true]);
		    $taxonomies = array_filter($taxonomies, function ($taxonomy){ return is_taxonomy_viewable($taxonomy); });
	    }
	    else{

		    $taxonomies = get_taxonomies();
	    }

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
     * @return TermCollection
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $criteria['fields'] = 'ids';

        $criteria['number'] = max(0, $limit?:0);

		if( !isset($criteria['parent']) )
			$criteria['parent'] = 0;

        if( $offset )
            $criteria['offset'] = $offset;

        if( $orderBy )
            $criteria = ['orderby' => $orderBy[0], 'order' => $orderBy[1]??'DESC'];

        return new TermCollection($criteria);
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
