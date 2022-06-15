<?php


namespace Metabolism\WordpressBundle\Helper;

use ArrayAccess;
use Metabolism\WordpressBundle\Entity\Entity;

use Metabolism\WordpressBundle\Factory\Factory,
	Metabolism\WordpressBundle\Factory\PostFactory,
	Metabolism\WordpressBundle\Factory\TermFactory;

class MetaHelper implements ArrayAccess
{
	private $objects;
	private $id;
	private $type;

    /**
     * MetaHelper constructor.
     *
     * @param $id
     * @param bool $type
     * @param bool $load_value
     */
	public function __construct( $id=false, $type=false, $load_value=false )
	{
		if( !$id )
			return;

        $this->id = $id;
		$this->type = $type;

        if( $load_value )
            $this->load();
	}


    /**
     * Magic method to check properties
     *
     * @param $id
     * @return bool
     */
	public function __isset($id) {

		return $this->has($id);
	}


    /**
     * Magic method to load properties
     *
     * @param $id
     * @return null|string|array|object
     */
	public function __get($id) {

		return $this->getValue($id);
	}


    /**
     * Magic method to load properties
     *
     * @param $id
     * @param $args
     * @return null|string|array|object
     */
	public function __call($id, $args) {

		return $this->getValue($id);
	}


	/**
	 * @param $id
	 * @return bool
	 */
	public function has($id){

        return (bool)$this->getValue($id);
	}


	/**
	 * @param
	 * @return array
	 */
	public function load(){

        if( $this->type == 'post' )
            $this->objects = get_post_meta($this->id);
        elseif( $this->type == 'term' )
            $this->objects = get_term_meta($this->id);
        elseif( $this->type == 'user' )
            $this->objects = get_user_meta($this->id);

        return $this->objects;
	}


	/**
	 * @param $id
	 * @return null|string|array|object
	 */
	public function getValue($id){

        if( $value = $this->objects[$id]??false )
            return $value;

        if( $this->type == 'post' )
            return get_post_meta($this->id, $id, true);
        elseif( $this->type == 'term' )
            return get_term_meta($this->id, $id, true);
        elseif( $this->type == 'user' )
            return get_user_meta($this->id, $id, true);

        return null;
	}


    /**
     * @param $id
     * @param $value
     * @param bool $updateField
     * @return void
     */
    public function setValue($id, $value, $updateField=false){

        $this->objects[$id] = $value;

        if( $updateField ){

            if( $this->type == 'post' )
                update_post_meta($this->id, $id, $value);
            elseif( $this->type == 'term' )
                update_term_meta($this->id, $id, $value);
            elseif( $this->type == 'user' )
                update_user_meta($this->id, $id, $value);
        }
    }

    public function offsetExists($offset)
    {
       return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->getValue($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->setValue($offset, $value);
    }

    public function offsetUnset($offset)
    {
        if( $this->has($offset) )
            unset($this->objects[$offset]);
    }
}
