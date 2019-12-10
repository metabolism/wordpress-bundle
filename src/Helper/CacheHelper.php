<?php

namespace Metabolism\WordpressBundle\Helper;

use Symfony\Component\HttpFoundation\Response;

class Cache {

	public function __construct(){}

	/**
	 * Set cache, redundant with WP_Object_Cache::add
	 */
	public static function set($key, $data, $group='app', $expire=60*60*12){

		if( !is_string($key) )
			$key = json_encode($key);

		return wp_cache_add($key, $data, $group, $expire);
	}


	/**
	 * Get cache, redundant with WP_Object_Cache::add
	 */
	public static function get($key, $group='app'){

		if( !is_string($key) )
			$key = json_encode($key);

		return wp_cache_get($key, $group);
	}


	/**
	 * Delete cache, redundant with WP_Object_Cache::add
	 */
	public static function delete($key, $group='app'){

		if( !is_string($key) )
			$key = json_encode($key);

		return wp_cache_delete($key, $group);
	}


	/**
	 * Clear cache completely
	 */
	public function clear(){

		wp_cache_flush();

		$status = $this->rrmdir(BASE_URI.'/var/cache');

		if( $status )
			$response = new Response('1');
		else
			$response = new Response('0', 500);

		$response->setSharedMaxAge(0);

		return $response;
	}


	/**
	 * Purge cache
	 */
	public function purge(){

		list($url, $status) = $this->purgeUrl();

		if( !is_wp_error($status) )
			$response = new Response('1');
		else
			$response = new Response('0', 500);

		$response->setSharedMaxAge(0);
		return $response;
	}


	/**
	 * Purge cache
	 * @param bool $url
	 * @return array|\WP_Error
	 */
	public function purgeUrl($url=false){

		if( !$url )
			$url = get_home_url(null, '*');

		$varnish = isset($_SERVER['VARNISH_IP'])?$_SERVER['VARNISH_IP']:false;

		$args = [
			'method' => 'PURGE',
			'headers' => ['Host' => $_SERVER['HTTP_HOST']],
			'sslverify' => false
		];

		if( $varnish )
			$url = str_replace($_SERVER['HTTP_HOST'], $varnish, $url);

		return [$url, wp_remote_request($url, $args)];
	}


	/**
	 * Recursive rmdir
	 * @param string $dir
	 * @return bool
	 */
	public function rrmdir($dir) {

		$status = true;

		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (is_dir($dir."/".$object))
						$status = $this->rrmdir($dir."/".$object) && $status;
					else
						$status = @unlink($dir."/".$object) && $status;
				}
			}
			$status = @rmdir($dir) && $status;
		}

		return $status;
	}
}
