<?php

namespace Metabolism\WordpressBundle\Helper;

class DataHelper {

    private $class;
    private $method;
    private $key;

    public function __construct($class, $method, $key){

        $this->class = $class;
        $this->method = $method;
        $this->key = $key;
    }

    public function __call($id, $args){

        if( !method_exists($this->class, $this->method) )
            return trigger_error('Method ' . $this->method . ' do not exist', E_USER_WARNING);

        //trigger_error('Direct access to blog data will be removed, please use {{ blog.' . $this->key . '(\''.$id.'\') }} instead', E_USER_NOTICE);
        return call_user_func_array([$this->class, $this->method], [$id]);
    }

    public function __toString(){

        if( !method_exists($this->class, $this->method) )
            return trigger_error('Method ' . $this->method . ' do not exist', E_USER_WARNING);

        $data = call_user_func([$this->class, $this->method]);

        if( !is_string($data) ){

            //trigger_error('Method ' . $this->method . ' do not return string', E_USER_WARNING);
            return '';
        }

        //trigger_error('Direct access to blog data will be removed, please use {{ blog.' . $this->key . ' }} instead', E_USER_NOTICE);

        return $data;
    }
}