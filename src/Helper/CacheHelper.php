<?php

namespace Metabolism\WordpressBundle\Helper;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class Cache {

	public function __construct(){

		$this->output = $_REQUEST['output']??false;
	}

	/**
	 * Clear cache completely
	 */
	public function clear(){

		$response = $this->purge();

		if( $this->remove() && !is_wp_error($response) )
			$response = new Response('1');
		else
			$response = new Response('0', 500);

		$response->setSharedMaxAge(0);
		return $response;
	}

	/**
	 * Remove cache folder
	 */
	public function remove(){

		if( !empty(BASE_URI) ){

			$this->rrmdir(BASE_URI.'/var/cache');
			return true;
		}

		return false;
	}


	/**
	 * Purge cache
	 */
	public function purge($url=false){

		if( !$url )
			$url = get_home_url(null, '*');

		$args = ['method' => 'PURGE', 'headers' => ['Host' => $_SERVER['HTTP_HOST']], 'sslverify' => false];

		$url = str_replace($_SERVER['HTTP_HOST'], $_SERVER['SERVER_ADDR'], $url);

		return wp_remote_request($url, $args);
	}

	
	/**
	 * Recursive rmdir
	 * @param string $dir
	 */
	private function rrmdir($dir) {

		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (is_dir($dir."/".$object))
						$this->rrmdir($dir."/".$object);
					else
						unlink($dir."/".$object);
				}
			}
			rmdir($dir);
		}
	}
}
