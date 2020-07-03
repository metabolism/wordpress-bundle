<?php

namespace Metabolism\WordpressBundle\Plugin;


use Dflydev\DotAccessData\Data;
use Metabolism\WordpressBundle\Traits\SingletonTrait;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class UrlPlugin {

	use SingletonTrait;

	/**
	 * Add edition folder to option url
	 * @param $url
	 * @return mixed
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
	 * @param $url
	 * @return string
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
	 * @param $url
	 * @return mixed
	 */
	public function homeURL($url)
	{
		if( WP_FOLDER )
			return str_replace(WP_FOLDER, '', $url);
		else
			return $url;
	}

	/**
	 * Save post name when requesting for preview link
	 * @param $id
	 * @return mixed
	 */
	public function getPreviewPermalink($id){

		$post = get_post($id);

		if( $post->post_name ){
			$post->post_status = 'publish';
			return get_permalink($post);
		}

		$filter = isset($post->filter) ? $post->filter : false;

		list($permalink, $post_name) = get_sample_permalink($post);
		$preview_permalink = str_replace( array( '%pagename%', '%postname%' ), $post_name, esc_html( urldecode( $permalink ) ) );

		$post->filter = $filter;

		if($post->post_name != $post_name){
			wp_update_post([
				'ID'=> $post->ID,
				'post_name'=> $post_name
			]);
		}

		return $preview_permalink;
	}


	/**
	 * Get search url
	 * @param $s
	 * @return mixed
	 */
	public function getSearchLink($s){

	    global $wp_rewrite;

	    $s = remove_accents(sanitize_text_field($s));
        return $wp_rewrite->search_base.'/'.urlencode($s);
	}


	/**
	 * Symfony require real url so redirect preview url to real url
	 * ex /?post_type=project&p=899&preview=true redirect to /project/post-title?preview=true
	 */
	public function redirect(){

		require_once(ABSPATH . 'wp-admin/includes/post.php');

		if( isset($_GET['s']) ){

            $permalink = $this->getSearchLink($_GET['s']);
        }
		else{

            $id = isset($_GET['p'])?$_GET['p']:$_GET['page_id'];
            $permalink = $this->getPreviewPermalink($id);

            $query_args['preview'] = 'true';
            $permalink = add_query_arg( $query_args, $permalink );
        }

		wp_redirect($permalink);
		exit;
	}


	/**
	 * Symfony require real url so redirect preview url to real url
	 * ex /?post_type=project&p=899&preview=true redirect to /project/post-title?preview=true
	 * @param $permalink
	 * @param $post
	 * @return mixed
	 */
	public function previewPostLink($permalink, $post){

		if( $post->post_name == '' ){

			$permalink = $this->getPreviewPermalink($post);

			$query_args['preview'] = 'true';
			$permalink = add_query_arg( $query_args, $permalink );
		}

		return $permalink;
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
	 * Remove link when there is no template support
	 * @param $url
	 * @return string
	 */
	public function makePostRelative($url){

		$make_relative = apply_filters('wp-bundle/make_post_link_relative', true);
		return $make_relative ? wp_make_link_relative($url) : $url;
	}


	/**
	 * Remove link when there is no template support
	 * @param $url
	 * @return string
	 */
	public function makeAttachmentRelative($url){

		$make_relative = apply_filters('wp-bundle/make_attachment_link_relative', true);
		return $make_relative ? wp_make_link_relative($url) : $url;
	}


	/**
	 * Remove link when there is no template support
	 * @param $html
	 * @return string|string[]|null
	 */
	public function applyUrlMapping($html){

		$html = preg_replace('/<span id="sample-permalink"><a href="(.*)">(.*)<span/', '<span id="sample-permalink"><a href="'.URL_MAPPING.'$1">'.URL_MAPPING.'$2<span', $html);
		$html = preg_replace('/<a id="sample-permalink" href="(.*)">(.*)<\/a>/', '<a id="sample-permalink" href="'.URL_MAPPING.'$1">'.URL_MAPPING.'$2</a>', $html);
		return $html;
	}


	/**
	 * UrlPlugin constructor.
	 * @param Data $config
	 */
	public function __construct($config){

		add_filter('preview_post_link', [$this, 'previewPostLink'], 10, 2);
		add_filter('option_siteurl', [$this, 'optionSiteURL'] );
		add_filter('network_site_url', [$this, 'networkSiteURL'] );
		add_filter('home_url', [$this, 'homeURL'] );

		if( HEADLESS ){

			add_action( 'wp_before_admin_bar_render', [$this, 'removeAdminBarLinks'] );

			if( URL_MAPPING ){
				add_filter('get_sample_permalink_html', [$this, 'applyUrlMapping'] );
			}
		}

		add_action('init', function()
		{
			// Handle subfolder in url
			if ( is_feed() )
				return;

			if( !is_admin() && (isset($_GET['preview'], $_GET['p']) || isset($_GET['preview'], $_GET['page_id']) || isset($_GET['s']) ) )
				$this->redirect();

			$filters = array(
				'post_link',
				'post_type_link',
				'page_link',
				'get_shortlink',
				'post_type_archive_link',
				'get_pagenum_link',
				'get_comments_pagenum_link',
				'term_link',
				'search_link',
				'day_link',
				'month_link',
				'year_link'
			);

			foreach ( $filters as $filter )
				add_filter( $filter, [$this, 'makePostRelative'] );

			$filters = array(
				'attachment_link',
				'wp_get_attachment_url'
			);

			foreach ( $filters as $filter )
				add_filter( $filter, [$this, 'makeAttachmentRelative'] );
		});
	}
}
