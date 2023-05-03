<?php

namespace Metabolism\WordpressBundle\Entity;

use ArrayIterator;
use Metabolism\WordpressBundle\Factory\PostFactory;
use Metabolism\WordpressBundle\Service\PaginationService;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class PostCollection implements \IteratorAggregate, \Countable, \ArrayAccess {

	private $query=false;

    protected $items=[];

	protected $pagination;

	/**
	 * @param array|\WP_Query|null $query
	 */
	public function __construct($query=null)
	{
        if( $query ){

			if( $query instanceof \WP_Query )
				$this->query = $query;
			else
				$this->query = new \WP_Query( $query );

			if( $this->query->posts )
				$this->setPosts($this->query->posts);
        }
    }

	/**
	 * @return int[]
	 */
	public function getPosts(){

		return $this->query->posts;
	}

    /**
     * @param array $posts
     * @return void
     */
    public function setPosts(array $posts){

        $posts = array_filter($posts);
        $items = [];

        foreach ($posts as $post)
            $items[] = PostFactory::create( $post );

        $this->items = array_filter($items);
    }


	/**
	 * @param $args
	 * @return array
	 */
	public function getPagination($args=[]){

		if( !empty($args) ){

			$paginationService = new PaginationService();
			return $paginationService->build($args, $this->query);
		}

		if( is_null($this->pagination) ){

			$paginationService = new PaginationService();
			$this->pagination = $paginationService->build($args, $this->query);
		}

		return $this->pagination;
	}


	/**
	 * @return ArrayIterator|Post[]
	 */
	public function getIterator(): \Traversable {

		return new ArrayIterator($this->items);
	}


	/**
	 * @return \WP_Query
	 */
	public function getQuery() {

		return $this->query;
	}


	/**
	 * Get total post count
	 *
	 * @return int
	 */
	public function count(): int
	{
		return $this->query ? $this->query->found_posts : count($this->items);
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
