<?php

namespace Metabolism\WordpressBundle\Entity;

use ArrayIterator;
use Metabolism\WordpressBundle\Factory\PostFactory;
use Metabolism\WordpressBundle\Factory\TermFactory;
use Metabolism\WordpressBundle\Service\PaginationService;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class TermCollection implements \IteratorAggregate, \Countable, \ArrayAccess {

	private $query=false;

    protected $items=[];
    protected $count=0;

	protected $pagination;

	/**
	 * @param array|null $query
	 */
	public function __construct(?array $query=null)
	{
        if( $query ){

            $this->query = new \WP_Term_Query( $query );
            $this->setTerms($this->query->terms);
        }
    }


    /**
     * @param array $terms
     * @return void
     */
    public function setTerms(array $terms){

	    $terms = array_unique(array_filter($terms));
        $items = [];

        foreach ($terms as $term)
            $items[] = TermFactory::create( $term );

        $this->items = array_filter($items);
    }


	/**
	 * @return ArrayIterator|Post[]
	 */
	public function getIterator() {

		return new ArrayIterator($this->items);
	}


	/**
	 * @return \WP_Term_Query
	 */
	public function getQuery() {

		return $this->query;
	}


	/**
	 * Get total post count
	 *
	 * @return int
	 */
	public function count()
	{
		return $this->query ? wp_count_terms($this->query->query_vars) : count($this->items);
	}

	/**
	 * @param $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return isset($this->items[$offset]);
	}

	/**
	 * @param $offset
	 * @return Post|null
	 */
	public function offsetGet($offset)
	{
		return $this->items[$offset]??null;
	}

	/**
	 * @param $offset
	 * @param $value
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->items[$offset] = $value;
	}

	/**
	 * @param $offset
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->items[$offset]);
	}
}
