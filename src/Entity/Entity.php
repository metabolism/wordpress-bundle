<?php

namespace Metabolism\WordpressBundle\Entity;

use Metabolism\WordpressBundle\Helper\ACF;

/**
 * Class Entity
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Entity
{
	public static $remove = [
		'xfn', 'db_id', 'post_mime_type', 'ping_status', 'to_ping', 'pinged', '_edit_lock',
		'guid', 'filter', 'post_content_filtered', 'url', 'name', 'author_IP', 'agent'
	];

	public $ID;
	public $entity;
	public static $date_format = false;

	private $custom_fields=false;
	private $imported=false;


	/**
	 * @param $info
	 * @param bool $remove
	 * @param bool $replace
	 */
	public function import($info, $remove=false , $replace=false )
	{
		$info = self::normalize($info, $remove, $replace);

		if ( is_object($info) )
			$info = get_object_vars($info);

		if ( is_array($info) )
		{
			foreach ( $info as $key => $value )
			{
				if ( $key === '' || ord($key[0]) === 0 )
					continue;

				if ( !empty($key) && !method_exists($this, $key) && property_exists($this, $key) )
					$this->$key = $value;
			}
		}

		$this->imported = true;
	}


	/**
	 * Return true if all fields have been loaded to the entity
	 */
	public function loaded()
	{
		return (!$this->custom_fields || $this->custom_fields->loaded()) && $this->imported;
	}


	/**
	 * Magic method to load async properties
	 *
	 * @param $method
	 * @param $arguments
	 * @return string
	 */
	public function __call($method, $arguments) {

		if( !$this->loaded() )
		{
			echo ' loaded for '.$method.' ';
			$this->bindCustomFields(true);
			return isset($this->$method)?$this->$method:'';
		}

		return '';
	}


	/**
	 * Return true if id exists
	 */
	public function exist()
	{
		return is_int( $this->ID );
	}


	/**
	 * load custom fields data
	 * @param $id
	 */
	protected function addCustomFields( $id)
	{
		if( class_exists('ACF') && !$this->custom_fields )
		{
			$this->custom_fields = new ACF( $id );
			$this->bindCustomFields();
		}
	}


	/**
	 * Bind custom fields as members of the post
	 * @param bool $force
	 */
	protected function bindCustomFields($force=false )
	{
		if( $this->custom_fields )
		{
			$objects = $this->custom_fields->get($force);

			if( $objects && is_array($objects) )
			{
				foreach ($objects as $name => $value )
				{
					$this->$name = $value;
				}
			}
		}
	}


	/**
	 * @param $object
	 * @param bool $remove
	 * @param bool $replace
	 * @return array|bool
	 */
	public static function normalize($object, $remove=false, $replace=false)
	{
		if( is_object($object) )
			$object = get_object_vars($object);

		if( !is_array($object) )
			return false;

		if( isset($object['url']) )
			$object['link'] = $object['url'];

		if( isset($object['post_author']) && is_string($object['post_author']) )
			$object['post_author'] = intval($object['post_author']);

		if( isset($object['comment_count']) && is_string($object['comment_count']) )
			$object['comment_count'] = intval($object['comment_count']);

		if( isset($object['name']) && !isset($object['title']) )
			$object['title'] = $object['name'];

		if( !self::$date_format )
			self::$date_format = get_option('date_format');

		if( isset($object['post_date']) ){
			$object['post_date'] = (string) mysql2date( self::$date_format, $object['post_date']);
			$object['post_date'] = apply_filters('get_the_date', $object['post_date'], self::$date_format);
		}

		if( isset($object['post_modified']) ){
			$object['post_modified'] = (string) mysql2date( self::$date_format, $object['post_modified']);
			$object['post_modified'] = apply_filters('get_the_date', $object['post_modified'], self::$date_format);
		}

		foreach(self::$remove as $prop){

			if( isset($object[$prop]) )
				unset($object[$prop]);
		}

		if( isset($object['classes']) && count($object['classes']) )
		{
			if( empty($object['classes'][0]))
				array_shift($object['classes']);

			$object['class'] = implode(' ', $object['classes']);
		}

		if( $remove )
		{
			foreach($object as $key=>$value)
			{
				if( strpos($key, $remove) === 0 )
					unset($object[$key]);
			}
		}

		foreach($object as $key=>$value)
		{
			if($replace && strpos($key, $replace) === 0 ){

				$new_key = str_replace($replace,'', $key);

				if( !isset($object[$new_key]) || empty($object[$new_key]))
					$object[$new_key] = $value;

				unset($object[$key]);
			}
		}

		return $object;
	}
}
