<?php

namespace Metabolism\WordpressBundle\Factory;

class Factory {

	protected static function camelCase($str)
	{
		// non-alpha and non-numeric characters become spaces
		$str = preg_replace('/[^a-z0-9]+/i', ' ', $str);
		$str = trim($str);
		// uppercase the first character of each word
		$str = ucwords($str);
		$str = str_replace(" ", "", $str);

		return $str;
	}

	protected static function loadFromCache($id, $type='object'){

		return wp_cache_get( $id, $type.'_factory' );
	}

	protected static function saveToCache($id, $object, $type){

		return wp_cache_set( $id, $object, $type.'_factory' );
	}

	public static function create($id, $class, $default_class=false){

		$post = self::loadFromCache($id, $class);

		if( $post )
			return $post;

		$classname = self::camelCase($class);

		$app_classname = 'App\Entity\\'.$classname;

		if( class_exists($app_classname) ){

			$post = new $app_classname($id);
		}
		else{

			$bundle_classname = 'Metabolism\WordpressBundle\Entity\\'.$classname;

			if( class_exists($bundle_classname) ){

				$post = new $bundle_classname($id);
			}
			elseif( $default_class ){

				$post = self::create($id, $default_class);
			}
		}

		if( $post && $post->loaded() )
			self::saveToCache($id, $post, $class);

		return $post;
	}
}
