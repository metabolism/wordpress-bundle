<?php

namespace Metabolism\WordpressBundle\Helper;

class FunctionHelper {

    private $function;
	private $data = [];

    public function __construct($function){

        $this->function = $function;
    }

    public function __call($id, $args=[]){

        $function = $this->function;

		if( !isset($this->data[$id]) )
			$this->data[$id] = $function($id);

		return $this->data[$id];
    }
}
