<?php

namespace Metabolism\WordpressBundle\Helper;

use Symfony\Component\HttpFoundation\Response;

class FeedHelper {

	public function doAction($feed){

		ob_start();
		@do_feed();
		$content = ob_get_contents();
		ob_end_clean();

		$response = new Response($content);
		$response->headers->set('Content-Type', feed_content_type( $feed ) . '; charset=' . get_option( 'blog_charset' ));
		$response->headers->set('Content-Disposition', 'inline');

		return $response;
	}
}
