<?php

namespace Metabolism\WordpressBundle\Helper;

use ArrayAccess;

class MetaHelper implements ArrayAccess
{
	private $objects;
	private $id;
	private $type;
	private $blog_id;
	private $network_id;

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

        $this->blog_id = get_current_blog_id();
        $this->network_id = get_current_network_id();
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
        elseif( $this->type == 'blog' && !is_multisite() )
            $this->objects = get_site_meta($this->blog_id);

        return $this->objects;
	}


	/**
	 * @param $key
	 * @return null|string|array|object
	 */
	public function getValue($key){

        if( isset($this->objects[$key]) )
            return $this->objects[$key];

        if( $this->type == 'post' )
            $this->objects[$key] = get_post_meta($this->id, $key, true);
        elseif( $this->type == 'term' )
            $this->objects[$key] = get_term_meta($this->id, $key, true);
        elseif( $this->type == 'user' )
            $this->objects[$key] = get_user_meta($this->id, $key, true);
        elseif( $this->type == 'blog' ){

            if( is_multisite() )
                $this->objects[$key] = get_network_option($this->network_id, $key);
            else
                $this->objects[$key] = get_site_meta($this->blog_id, $key, true);
        }
        else
            $this->objects[$key] = false;

        return $this->objects[$key];
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
            elseif( $this->type == 'blog' ){

                if( is_multisite() )
                    update_network_option($this->network_id, $id, $value);
                else
                    update_site_meta($this->blog_id, $id, $value);
            }
        }
    }

	/**
	 * @param $offset
	 * @return bool
	 */
	public function offsetExists($offset): bool
    {
       return $this->has($offset);
    }

	/**
	 * @param $offset
	 * @return mixed
	 */
    #[\ReturnTypeWillChange]
	public function offsetGet($offset)
    {
        return $this->getValue($offset);
    }

	/**
	 * @param $offset
	 * @param $value
	 * @return void
	 */
	public function offsetSet($offset, $value): void
    {
        $this->setValue($offset, $value);
    }

	/**
	 * @param $offset
	 * @return void
	 */
	public function offsetUnset($offset): void
    {
        if( $this->has($offset) )
            unset($this->objects[$offset]);
    }
}
