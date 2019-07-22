<?php

namespace Metabolism\WordpressBundle\Helper;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class Cache {

	public function __construct(){

		$this->output = $_REQUEST['output']??false;
	}

	/**
	 * Clear cache url
	 */
	public function clear(){

		if( $this->purge() )
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

		if( !empty(BASE_URI) ){

			$this->rrmdir(BASE_URI.'/var/cache');
			return true;
		}

		return false;
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
