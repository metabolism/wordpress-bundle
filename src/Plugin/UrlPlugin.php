<?php

namespace Metabolism\WordpressBundle\Plugin;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class UrlPlugin {


	/**
	 * Add edition folder to option url
	 */
	public function networkSiteURL($url)
	{
		if( WP_FOLDER && strpos($url,WP_FOLDER) === false )
			return str_replace('/wp-admin', WP_FOLDER.'/wp-admin', $url);
		else
			return $url;
	}


	/**
	 * Add edition folder to option url
	 */
	public function optionSiteURL($url)
	{
		if( WP_FOLDER )
			return strpos($url, WP_FOLDER) === false ? $url.WP_FOLDER : $url;
		else
			return $url;
	}


	/**
	 * Add edition folder to option url
	 */
	public function homeURL($url)
	{
		if( WP_FOLDER )
			return str_replace(WP_FOLDER, '', $url);
		else
			return $url;
	}

	/**
	 * Add search post type filtered
	 */
	public function addRewriteRules(){

		global $wp_rewrite;

		$search_post_type_permastuct = str_replace('/%search%', '/%post_type%/%search%', $wp_rewrite->get_search_permastruct());
		$regex = str_replace('%search%', '([^/]*)', str_replace('%post_type%', '([^/]*)', $search_post_type_permastuct));
		add_rewrite_rule('^'.$regex.'/'.$wp_rewrite->pagination_base.'/([0-9]{1,})/?', 'index.php?s=$matches[2]&post_type=$matches[1]&paged=$matches[3]', 'top');
		add_rewrite_rule('^'.$regex.'/?', 'index.php?s=$matches[2]&post_type=$matches[1]', 'top');

		$wp_rewrite->search_post_type_structure = $search_post_type_permastuct;
	}


	public function __construct($config)
	{


		add_filter('option_siteurl', [$this, 'optionSiteURL'] );
		add_filter('network_site_url', [$this, 'networkSiteURL'] );
		add_filter('home_url', [$this, 'homeURL'] );
		add_action('init', [$this, 'addRewriteRules']);

		if( is_admin() )
			return;

		add_action('init', function()
		{

			// Handle subfolder in url
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
