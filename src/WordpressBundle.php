<?php

namespace Metabolism\WordpressBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class WordpressBundle extends Bundle
{
	public function boot()
	{
		$rootDir = $this->container->get('kernel')->getRootDir();

		include $rootDir.'/../web/edition/wp-load.php';

		//see wp-includes/class-wp.php, main function
		global $wp;

		$wp->init();
		$wp->parse_request();
		$wp->query_posts();

		$this->registerGlobals();

		do_action_ref_array( 'wp', array( &$wp ) );
	}

	protected function registerGlobals() {

		global $wp_query, $wp;

		// Extract updated query vars back into global namespace.
		foreach ( (array) $wp_query->query_vars as $key => $value )
			$GLOBALS[ $key ] = $value;

		$GLOBALS['query_string'] = $wp->query_string;
		$GLOBALS['posts'] = & $wp_query->posts;
		$GLOBALS['post'] = isset( $wp_query->post ) ? $wp_query->post : null;
	}

}
