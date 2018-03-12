<?php

namespace Metabolism\WordpressBundle\Helper;

class WordpressHelper
{
	// mostly override class-wp.php to remove $GLOBALS['request'] global and handle_404

	public static function load()
	{
		include BASE_URI.'/web/edition/wp-load.php';

		global $wp;

		$wp->init();
		$wp->parse_request();
		$wp->send_headers();
		$wp->query_posts();

		self::register_globals();

		do_action_ref_array( 'wp', array( &$wp ) );
	}

	public static function register_globals() {

		global $wp_query, $wp;

		// Extract updated query vars back into global namespace.
		foreach ( (array) $wp_query->query_vars as $key => $value ) {
			$GLOBALS[ $key ] = $value;
		}

		$GLOBALS['query_string'] = $wp->query_string;
		$GLOBALS['posts'] = & $wp_query->posts;
		$GLOBALS['post'] = isset( $wp_query->post ) ? $wp_query->post : null;

		if ( $wp_query->is_single() || $wp_query->is_page() ) {
			$GLOBALS['more']   = 1;
			$GLOBALS['single'] = 1;
		}

		if ( $wp_query->is_author() && isset( $wp_query->post ) )
			$GLOBALS['authordata'] = get_userdata( $wp_query->post->post_author );
	}
}
