<?php

namespace Metabolism\WordpressBundle\Factory;

use Metabolism\WordpressBundle\Entity\Entity;

class Factory {

	/**
	 * Generate classname from string
	 * @param $str
	 * @return string
	 */
	public static function getClassname($str)
	{
		// non-alpha and non-numeric characters become spaces
		$str = preg_replace('/[^a-z0-9]+/i', ' ', $str);
		$str = trim($str);
		// uppercase the first character of each word
		$str = ucwords($str);
		$str = str_replace(" ", "", $str);

		return $str;
	}

	/**
	 * Retrieves the cache contents from the cache by key and group.
	 * @param $id
	 * @param string $type
	 * @return bool|mixed
	 */
	protected static function loadFromCache($id, $type='object'){

		return wp_cache_get( $id, $type.'_factory' );
	}

	/**
	 * Saves the data to the cache.
	 * @param $id
	 * @param $object
	 * @param $type
	 * @return bool
	 */
	protected static function saveToCache($id, $object, $type){

		return wp_cache_set( $id, $object, $type.'_factory' );
	}

	/**
	 * Create entity
	 * @param $id
	 * @param $class
	 * @param bool $default_class
	 * @return Entity|mixed
	 */
	public static function create($id, $class, $default_class=false){

		$item = self::loadFromCache($id, $class);

		if( $item )
			return $item;

		$classname = self::getClassname($class);

		$app_classname = 'App\Entity\\'.$classname;

		if( class_exists($app_classname) ){

			$item = new $app_classname($id);
		}
		else{

			$bundle_classname = 'Metabolism\WordpressBundle\Entity\\'.$classname;

			if( class_exists($bundle_classname) ){

				$item = new $bundle_classname($id);
			}
			elseif( $default_class ){

				$item = self::create($id, $default_class);
			}
		}

		if( !$item->exist() )
			$item = false;

		if( !$item || $item->loaded() )
			self::saveToCache($id, $item, $class);

		return $item;
	}
}
