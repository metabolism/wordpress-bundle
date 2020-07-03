<?php

namespace Metabolism\WordpressBundle\Helper;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class Robots {

	public function doAction(){

		ob_start();
		do_action( 'do_robots' );
		$content = ob_get_contents();
		ob_end_clean();

		$site_url = parse_url( site_url() );
		$path     = ( ! empty( $site_url['path'] ) ) ? $site_url['path'] : '';

		$content = str_replace("Disallow: $path/wp-admin/", "Disallow: $path", $content);
		$content = str_replace("Allow: $path/wp-admin/admin-ajax.php\n", "Disallow: /wp-bundle\n", $content);

		$response = new Response($content);
		$response->headers->set('Content-Type', 'text/plain; charset=utf-8');

		return $response;
	}
}
