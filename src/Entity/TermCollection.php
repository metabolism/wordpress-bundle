<?php

namespace Metabolism\WordpressBundle\Entity;

use ArrayIterator;
use Metabolism\WordpressBundle\Factory\TermFactory;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class TermCollection implements \IteratorAggregate, \Countable, \ArrayAccess {

	private $query=false;

	private $args=[];

    protected $items=[];

	protected $pagination;

	/**
	 * @param array|\WP_Term_Query|null $args
	 */
	public function __construct($args=null)
	{
        if( $args ){

			if( $args instanceof \WP_Term_Query ){

                $this->query = $args;
            }
			else{

                $this->args = $args;

                if( !isset($args['fields']) )
                    $args['fields'] = 'ids';

                $this->query = new \WP_Term_Query( $args );
            }

			if( $this->query->terms )
				$this->setItems($this->query->terms);
        }
    }

    /**
     * @return array
     */
    public function getArgs(){

        return $this->args;
    }

	/**
	 * @return array
	 */
	public function getItems(){

		return $this->items;
	}


    /**
     * @param array $terms
     * @return void
     */
    public function setItems(array $terms){

	    $terms = array_unique(array_filter($terms));
        $items = [];

        if( !isset($this->args['fields']) ){

            foreach ($terms as $term)
                $items[] = TermFactory::create( $term );
        }
        else{

            $items = $terms;
        }

        $this->items = array_filter($items);
    }


	/**
	 * @return ArrayIterator|Term[]
	 */
	public function getIterator(): \Traversable {

		return new ArrayIterator($this->items);
	}


	/**
	 * @return \WP_Term_Query
	 */
	public function getQuery() {

		return $this->query;
	}


	/**
	 * Get total term count
	 *
	 * @return int
	 */
	public function count(): int
	{
		return count($this->items);
	}

	/**
	 * @param $offset
	 * @return bool
	 */
	public function offsetExists($offset): bool
	{
		return isset($this->items[$offset]);
	}

	/**
	 * @param $offset
	 * @return Term|null
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
	public function offsetSet($offset, $value): void
	{
		$this->items[$offset] = $value;
	}

	/**
	 * @param $offset
	 * @return void
	 */
	public function offsetUnset($offset): void
	{
		unset($this->items[$offset]);
	}
}
