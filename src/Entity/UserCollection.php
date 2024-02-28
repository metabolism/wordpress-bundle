<?php

namespace Metabolism\WordpressBundle\Entity;

use ArrayIterator;
use Metabolism\WordpressBundle\Factory\Factory;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class UserCollection implements \IteratorAggregate, \Countable, \ArrayAccess {

	private $query=false;

	private $args=[];

    protected $items=[];

	protected $pagination;

	/**
	 * @param array|\WP_User_Query|null $args
	 */
	public function __construct($args=null)
	{
        if( $args ){

			if( $args instanceof \WP_User_Query ){

                $this->query = $args;
            }
			else{

                $this->args = $args;

                if( !isset($args['fields']) )
                    $args['fields'] = 'ID';

                $this->query = new \WP_User_Query( $args );
            }

			if( $users  = $this->query->get_results() )
				$this->setItems( $users );
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
     * @param array $users
     * @return void
     */
    public function setItems(array $users){

	    $users = array_unique(array_filter($users));
        $items = [];

        if( !isset($this->args['fields']) ){

        foreach ($users as $user)
            $items[] = Factory::create( $user, 'user' );
        }
        else{

            $items = $users;
        }

        $this->items = array_filter($items);
    }


	/**
	 * @return ArrayIterator|User[]
	 */
	public function getIterator() {

		return new ArrayIterator($this->items);
	}


	/**
	 * @return \WP_User_Query
	 */
	public function getQuery() {

		return $this->query;
	}


	/**
	 * Get total user count
	 *
	 * @return int
	 */
	public function count() : int
	{
		return $this->query ? $this->query->get_total() : count($this->items);
	}

	/**
	 * @param $offset
	 * @return bool
	 */
	public function offsetExists($offset) : bool
	{
		return isset($this->items[$offset]);
	}

	/**
	 * @param $offset
	 * @return User|null
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
