<?php

namespace Metabolism\WordpressBundle\Entity;

use ArrayIterator;
use Metabolism\WordpressBundle\Factory\Factory;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class CommentCollection implements \IteratorAggregate, \Countable, \ArrayAccess {

	private $query=false;

    private $args=[];

    protected $items=[];

	protected $pagination;

	/**
	 * @param array|\WP_Comment_Query|null $args
	 */
	public function __construct($args=null)
	{
        if( $args ){

			if( $args instanceof \WP_Comment_Query ){

                $this->query = $args;
            }
			else{

                $this->args = $args;

                if( !isset($args['fields']) )
                    $args['fields'] = 'ids';

                $this->query = new \WP_Comment_Query($args);
            }

			if( $comments = $this->query->get_comments() )
				$this->setItems( $comments );
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
     * @param array $comments
     * @return void
     */
    public function setItems(array $comments){

        $comments = array_unique(array_filter($comments));
        $items = [];

        if( !isset($this->args['fields']) ){

            foreach ($comments as $comment)
                $items[] = Factory::create( $comment, 'comment' );
        }
        else{

            $items = $comments;
        }

        $this->items = array_filter($items);
    }


	/**
	 * @return ArrayIterator|Comment[]
	 */
	public function getIterator() {

		return new ArrayIterator($this->items);
	}


	/**
	 * @return \WP_Comment_Query
	 */
	public function getQuery() {

		return $this->query;
	}


	/**
	 * Get total comment count
	 *
	 * @return int
	 */
	public function count() : int
	{
        if( $args = $this->getArgs() ){

            $args['count'] = true;

            return $this->query->query($args);
        }
        else{

            return count($this->items);
        }
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
	 * @return Comment|null
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
	public function offsetSet($offset, $value) : void
	{
		$this->items[$offset] = $value;
	}

	/**
	 * @param $offset
	 * @return void
	 */
	public function offsetUnset($offset) : void
	{
		unset($this->items[$offset]);
	}
}
