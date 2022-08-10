<?php

namespace Metabolism\WordpressBundle\Helper;

class ClassHelper {

    private $class;
	private $instances = [];

    public function __construct($class){

        $this->class = $class;
    }

    public function __call($id, $args){

        $class = $this->class;

		if( !isset($this->instances[$id]) )
			$this->instances[$id] = new $class($id);

		return $this->instances[$id];
    }
}
