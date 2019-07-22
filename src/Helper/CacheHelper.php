<?php

namespace Metabolism\WordpressBundle\Helper;

use Symfony\Component\HttpFoundation\Response;

class Cache {

	public function __construct(){}


	/**
	 * Clear cache completely
	 */
	public function clear(){

		$status = $this->purge() && $this->rrmdir(BASE_URI.'/var/cache');

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

		$status = $this->purgeUrl();

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

		$args = ['method' => 'PURGE', 'headers' => ['Host' => $_SERVER['HTTP_HOST']], 'sslverify' => false];

		$url = str_replace($_SERVER['HTTP_HOST'], $_SERVER['SERVER_ADDR'], $url);

		return wp_remote_request($url, $args);
	}


	/**
	 * Recursive rmdir
	 * @param string $dir
	 * @return bool
	 */
	private function rrmdir($dir) {

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
			$status = rmdir($dir) && $status;
		}

		return $status;
	}
}
