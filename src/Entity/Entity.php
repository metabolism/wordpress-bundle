<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Metabolism\WordpressBundle\Entity;

use Metabolism\WordpressBundle\Helper\ACFHelper as ACF;

/**
 * Class Post
 * @see \Timber\Term
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Entity
{
	public static $remove = [
		'xfn', 'db_id', 'comment_count', 'post_mime_type', 'ping_status', 'to_ping', 'pinged', 'comment_status',
		'guid', 'filter', 'post_content_filtered', 'url', 'name'
	];

	public $ID;

	public function import( $info, $remove=false, $force = false )
	{
		$info = self::normalize($info, $remove);

		if ( is_object($info) )
			$info = get_object_vars($info);

		if ( is_array($info) )
		{
			foreach ( $info as $key => $value )
			{
				if ( $key === '' || ord($key[0]) === 0 )
					continue;

				if ( !empty($key) && $force )
					$this->$key = $value;
				else if ( !empty($key) && !method_exists($this, $key) )
					$this->$key = $value;
			}
		}
	}


	/**
	 * Add ACF custom fields as members of the post
	 */
	protected function addCustomFields( $id )
	{
		$custom_fields = new ACF( $id );

		foreach ($custom_fields->get() as $name => $value )
		{
			$this->$name = $value;
		}
	}


	public static function normalize($object, $remove=false, $replace=false)
	{
		if( is_object($object) )
			$object = get_object_vars($object);

		if( isset($object['url']) )
			$object['link'] = $object['url'];

		if( isset($object['name']) and !isset($object['title']) )
			$object['title'] = $object['name'];

		foreach(self::$remove as $prop){

			if( isset($object[$prop]) )
				unset($object[$prop]);
		}

		if( isset($object['classes']) and count($object['classes']) )
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
			if( strpos($key, 'post_') === 0 ){
				$object[str_replace('post_','', $key)] = $value;
				unset($object[$key]);
			}
		}

		return $object;
	}
}
