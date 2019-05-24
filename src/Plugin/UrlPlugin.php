<?php

namespace Metabolism\WordpressBundle\Plugin;


use Dflydev\DotAccessData\Data;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class UrlPlugin {


	/**
	 * Add edition folder to option url
	 */
	public function networkSiteURL($url)
	{
		if( WP_FOLDER && strpos($url, WP_FOLDER) === false )
		{
			$url = str_replace('/wp-login', WP_FOLDER.'/wp-login', $url);
			$url = str_replace('/wp-admin', WP_FOLDER.'/wp-admin', $url);

			return $url;
		}
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

		$search_slug = get_option( 'search_rewrite_slug' );
		if( !empty($search_slug) )
			$wp_rewrite->search_base = $search_slug;

		$search_post_type_permastuct = str_replace('/%search%', '/%post_type%/%search%', $wp_rewrite->get_search_permastruct());
		$regex = str_replace('%search%', '([^/]*)', str_replace('%post_type%', '([^/]*)', $search_post_type_permastuct));
		add_rewrite_rule('^'.$regex.'/'.$wp_rewrite->pagination_base.'/([0-9]{1,})/?', 'index.php?s=$matches[2]&post_type=$matches[1]&paged=$matches[3]', 'top');
		add_rewrite_rule('^'.$regex.'/?', 'index.php?s=$matches[2]&post_type=$matches[1]', 'top');

		$wp_rewrite->search_post_type_structure = $search_post_type_permastuct;
	}

	/**
	 * Save post name when requesting for preview link
	 */
	public function previewPostLink($preview_link, $post){

		$filter = isset($post->filter) ? $post->filter : false;

		list($permalink, $post_name) = get_sample_permalink($post);

		$post->filter = $filter;

		if($post->post_name != $post_name){
			wp_update_post([
				'ID'=> $post->ID,
				'post_name'=> $post_name
			]);
		}

		return $preview_link;
	}

	/**
	 * Make link relative
	 */
	public function relativeLink($link){

		return str_replace(WP_HOME, '', $link);
	}

	/**
	 * Symfony require real url so redirect preview url to real url
	 * ex /?post_type=project&p=899&preview=true redirect to /project/post-title?preview=true
	 */
	public function redirect(){

		require_once(ABSPATH . 'wp-admin/includes/post.php');

		list($permalink, $post_name) =  get_sample_permalink($_GET['p']);
		$permalink = str_replace( array( '%pagename%', '%postname%' ), $post_name, esc_html( urldecode( $permalink ) ) );

		$query_args['preview'] = 'true';
		$permalink = add_query_arg( $query_args, $permalink );

		wp_redirect($permalink);
		exit;
	}

	/**
	 * Remove link when there is no template support
	 */
	public function removeAdminBarLinks(){

		global $wp_admin_bar;
		$wp_admin_bar->remove_menu('view-site');
		$wp_admin_bar->remove_menu('site-name');
	}


	/**
	 * UrlPlugin constructor.
	 * @param Data $config
	 */
	public function __construct($config){

		add_filter('preview_post_link', [$this, 'previewPostLink'], 10, 2 );

		add_filter('post_link', [$this, 'relativeLink']);
		add_filter('page_link', [$this, 'relativeLink']);
		add_filter('post_type_link', [$this, 'relativeLink']);
		add_filter('post_type_archive_link', [$this, 'relativeLink']);

		add_filter('option_siteurl', [$this, 'optionSiteURL'] );
		add_filter('network_site_url', [$this, 'networkSiteURL'] );
		add_filter('home_url', [$this, 'homeURL'] );

		if( !WP_FRONT )
			add_action( 'wp_before_admin_bar_render', [$this, 'removeAdminBarLinks'] );

		if( is_admin() )
			return;

		add_action('init', function()
		{
			// Handle subfolder in url
			if ( is_feed() || get_query_var( 'sitemap' ) )
				return;

			if( isset($_GET['preview'], $_GET['p']) )
				$this->redirect();

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
