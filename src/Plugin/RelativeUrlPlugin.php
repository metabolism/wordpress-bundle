<?php

namespace Metabolism\WordpressBundle\Plugin;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class RelativeUrlPlugin {

	public function __construct($config)
	{
		if( is_admin() )
			return;

		add_action('init', function()
		{
			if ( is_feed() || get_query_var( 'sitemap' ) )
				return;

			$filters = array(
				'post_link',
				'post_type_link',
				'page_link',
				'attachment_link',
				'get_shortlink',
				'post_type_archive_link',
				'get_pagenum_link',
				'get_comments_pagenum_link',
				'term_link',
				'search_link',
				'day_link',
				'month_link',
				'wp_get_attachment_url',
				'year_link'
			);

			foreach ( $filters as $filter )
				add_filter( $filter, 'wp_make_link_relative' );
		});
	}
}
