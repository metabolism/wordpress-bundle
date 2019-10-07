<?php

namespace Metabolism\WordpressBundle;

use Metabolism\WordpressBundle\Extension\TwigExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class WordpressBundle extends Bundle
{
	/**
	 * 	@see wp-includes/class-wp.php, main function
	 */
	public function boot()
	{
	    if( !isset($_SERVER['SERVER_NAME'] ) && (!isset($_SERVER['WP_INSTALLED']) || !$_SERVER['WP_INSTALLED']) )
	        return;

		$rootDir = $this->container->get('kernel')->getProjectDir();

		include $rootDir.'/public/edition/wp-load.php';

		global $wp;

		$wp->init();
		$wp->parse_request();
		$wp->query_posts();

		$this->registerGlobals();

		do_action_ref_array( 'wp', array( &$wp ) );

		do_action('template-redirect');

		if( $twig = $this->container->get('twig') ){

            $twigExtension = new TwigExtension();
            $twig->addExtension($twigExtension);
        }
	}

	/**
	 * Analyse query and load posts
	 */
	protected function registerGlobals() {

		global $wp_query, $wp;

		// Extract updated query vars back into global namespace.
		foreach ( (array) $wp_query->query_vars as $key => $value )
			$GLOBALS[ $key ] = $value;

		$GLOBALS['query_string'] = $wp->query_string;
		$GLOBALS['posts'] = & $wp_query->posts;
		$GLOBALS['post'] = isset( $wp_query->post ) ? $wp_query->post : null;

		if( !$wp_query->get_queried_object() ){

			$wp_query->is_single = false;
			$wp_query->is_singular = false;
		}
	}

}
