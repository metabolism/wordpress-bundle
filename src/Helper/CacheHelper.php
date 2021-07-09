<?php

namespace Metabolism\WordpressBundle\Helper;

use Symfony\Component\HttpFoundation\Response;

class CacheHelper {

	public function __construct(){}

	/**
	 * Set cache, redundant with WP_Object_Cache::add
	 * @param $key
	 * @param $data
	 * @param string $group
	 * @param float|int $expire
	 * @return bool
	 */
	public static function set($key, $data, $group='app', $expire=60*60*12){

		if( !is_string($key) )
			$key = json_encode($key);

		return wp_cache_add($key, $data, $group, $expire);
	}


	/**
	 * Get cache, redundant with WP_Object_Cache::add
	 * @param $key
	 * @param string $group
	 * @return bool
	 */
	public static function get($key, $group='app'){

		if( !is_string($key) )
			$key = json_encode($key);

		return wp_cache_get($key, $group);
	}


	/**
	 * Delete cache, redundant with WP_Object_Cache::add
	 * @param $key
	 * @param string $group
	 * @return bool
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
			$url = get_home_url(null, '.*');

		$varnish_ssl = isset($_SERVER['VARNISH_SSL'])?$_SERVER['VARNISH_SSL']:false;
        $result = [];

        $args = [
            'method' => 'PURGE',
            'headers' => [
                'host' => $_SERVER['HTTP_HOST'],
                'X-VC-Purge-Method' => 'regex',
                'X-VC-Purge-Host' => $_SERVER['HTTP_HOST']
            ],
            'sslverify' => false
        ];

		if( isset($_SERVER['VARNISH_IPS']) ){

            $varnish_ips = explode(',',$_SERVER['VARNISH_IPS']);
        }
		elseif( isset($_SERVER['VARNISH_IP']) ){

            $varnish_ips = [$_SERVER['VARNISH_IP']];
        }
        else{

            $response = wp_remote_request(str_replace('.*', '*', $url), $args);
            $result[] = ['url'=>$url, 'request'=>$response];

            return $result;
        }

		foreach ($varnish_ips as $varnish_ip){

			$varnish_url = str_replace($_SERVER['HTTP_HOST'], $varnish_ip, $url);

			if( !$varnish_ssl )
				$varnish_url = str_replace('https://', 'http://', $varnish_url);

			$response = wp_remote_request($varnish_url, $args);
            $result[] = ['url'=>$varnish_url, 'request'=>$response];
		}

		return $result;
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
