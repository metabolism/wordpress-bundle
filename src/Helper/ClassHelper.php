<?php

namespace Metabolism\WordpressBundle\Helper;

use ArrayAccess;

class ClassHelper implements ArrayAccess {

    private $class;
	private $instances = [];

    public function __construct($class){

        $this->class = $class;
    }

    public function __call($id, $args=[]){

        $class = $this->class;

		if( !isset($this->instances[$id]) )
			$this->instances[$id] = new $class($id);

		return $this->instances[$id];
    }

    /**
     * @param $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        $instance = $this->__call($offset);

        return $instance->exist();
    }

    /**
     * @param $offset
     * @return string|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->__call($offset);
    }

    /**
     * @param $offset
     * @param $value
     * @return void
     */
    public function offsetSet($offset, $value): void{}

    /**
     * @param $offset
     * @return void
     */
    public function offsetUnset($offset): void{}
}
