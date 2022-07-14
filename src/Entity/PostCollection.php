<?php

namespace Metabolism\WordpressBundle\Entity;

use ArrayIterator;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;
use JetBrains\PhpStorm\Internal\TentativeType;
use Metabolism\WordpressBundle\Factory\PostFactory;
use Metabolism\WordpressBundle\Service\PaginationService;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class PostCollection implements \IteratorAggregate, \Countable, \ArrayAccess {

	public $query=false;

    protected $items=[];
    protected $count=0;

	protected $pagination;

	/**
	 * @param $query
	 */
	public function __construct(?array $query=null)
	{
        if( $query ){

            $this->query = new \WP_Query( $query );
            $this->setPosts($this->query->posts);
        }
    }


    /**
     * @param array $posts
     * @return void
     */
    public function setPosts(array $posts){

        $posts = array_unique(array_filter($posts));
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

		if( is_null($this->pagination) ){

			$paginationService = new PaginationService();
			$this->pagination = $paginationService->build($args);
		}

		return $this->pagination;
	}


	/**
	 * @return ArrayIterator
	 */
	public function getIterator() {

		return new ArrayIterator($this->items);
	}


	/**
	 * Get total post count
	 *
	 * @return int
	 */
	public function count()
	{
		return $this->query ? $this->query->found_posts : count($this->items);
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
