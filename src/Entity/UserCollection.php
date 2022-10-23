<?php

namespace Metabolism\WordpressBundle\Entity;

use ArrayIterator;
use Metabolism\WordpressBundle\Factory\Factory;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class UserCollection implements \IteratorAggregate, \Countable, \ArrayAccess {

	private $query=false;

    protected $items=[];

	protected $pagination;

	/**
	 * @param array|\WP_User_Query|null $query
	 */
	public function __construct($query=null)
	{
        if( $query ){

			if( $query instanceof \WP_User_Query )
				$this->query = $query;
			else
				$this->query = new \WP_User_Query( $query );

			if( $users  = $this->query->get_results() )
				$this->setUsers( $users );
        }
    }

	/**
	 * @return int[]
	 */
	public function getUsers(){

		return $this->query->get_results();
	}

    /**
     * @param array $users
     * @return void
     */
    public function setUsers(array $users){

	    $users = array_unique(array_filter($users));
        $items = [];

        foreach ($users as $user)
            $items[] = Factory::create( $user, 'user' );

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
	public function count()
	{
		return $this->query ? $this->query->get_total() : count($this->items);
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
