<?php

namespace Metabolism\WordpressBundle\Provider;

/**
 * Class WPSEOProvider
 *
 * @package Metabolism\WordpressBundle\Provider
 */
class WPSEOProvider
{
	public static $preventRecursion=false;

	/**
	 * Disable editor options for seo taxonomy edition
	 * @param $settings
	 * @param $editor_id
	 * @return mixed
	 */
	public function editorSettings( $settings, $editor_id ){

		if ( $editor_id == 'description' && class_exists('WPSEO_Taxonomy') && \WPSEO_Taxonomy::is_term_edit( $GLOBALS['pagenow'] ) ) {

			$settings[ 'tinymce' ] = false;
			$settings[ 'wpautop' ] = false;
			$settings[ 'media_buttons' ] = false;
			$settings[ 'quicktags' ] = false;
			$settings[ 'default_editor' ] = '';
			$settings[ 'textarea_rows' ] = 4;
		}

		return $settings;
	}


	/**
	 * Allow editor to edit theme and wpseo options
	 */
	public function updateEditorRole(){

		$role_object = get_role( 'editor' );

		if( !$role_object->has_cap('wpseo_edit_advanced_metadata') )
			$role_object->add_cap( 'editor', 'wpseo_edit_advanced_metadata' );

		if( !$role_object->has_cap('wpseo_manage_options') )
			$role_object->add_cap( 'editor', 'wpseo_manage_options' );
	}


	/**
	 * Init admin
	 */
	public function init(){

		$this->updateEditorRole();
	}


	/**
	 * Remove trailing slash & query parameters
	 * @param $canonical
	 * @return mixed
	 */
	public function filterCanonical($canonical) {

		if( is_archive() ){
			$canon_page = get_pagenum_link(1);
			$canonical = explode('?', $canon_page);
			return $canonical[0];
		}

		$canonical = explode('?', $canonical);
		$canonicalFormat = (substr($canonical[0], -1) == '/') ? substr($canonical[0], 0, -1) : $canonical[0];

		return $canonicalFormat;
	}


	/**
	 * Add primary flagged term in first position
	 * @param $terms
	 * @param $postID
	 * @param $taxonomy
	 * @return array
	 */
	public function changeTermsOrder($terms, $postID, $taxonomy){

		if ( class_exists('WPSEO_Primary_Term') && !self::$preventRecursion ) {

			self::$preventRecursion = true;

			$wpseo_primary_term = new \WPSEO_Primary_Term( $taxonomy, $postID);
			$primary_term_id = $wpseo_primary_term->get_primary_term();

			if( $primary_term_id ){

				foreach ($terms as $key=>$term){

					if( $term->term_id == $primary_term_id)
						unset($terms[$key]);
				}

				$terms = array_merge([get_term($primary_term_id)], $terms);
			}

			self::$preventRecursion = false;
		}

		return $terms;
	}


	/**
	 * return true if wpseo title is filled
	 * @param $postID
	 * @return bool
	 */
	public static function hasTitle($postID){

		return strlen(get_post_meta($postID, '_yoast_wpseo_title', true)) > 1 ? true : false;
	}


	/**
	 * return true if wpseo description is filled
	 * @param $postID
	 * @return bool
	 */
	public static function hasDescription($postID){

		return strlen(get_post_meta($postID, '_yoast_wpseo_metadesc', true)) > 1 ? true : false;
	}


	/**
	 * add sitemap_index.xml to robots.txt
	 * @param $output
	 * @return string
	 */
	public static function sitemapToRobots( $output ) {

		$options = get_option( 'wpseo_xml' );

		if ( class_exists( 'WPSEO_Sitemaps' ) && $options['enablexmlsitemap'] == true ) {

			$homeURL = get_home_url();
			$output .= "Sitemap: $homeURL/sitemap_index.xml\n";
		}

		return $output;
	}



	/**
	 * Construct
	 */
	public function __construct()
	{
		add_action( 'admin_init', [$this, 'init'] );
		add_filter( 'get_the_terms', [$this, 'changeTermsOrder'], 10, 3);

		if( is_admin() ) {
			add_filter( 'wp_editor_settings', [$this, 'editorSettings'], 10, 2);
		}
		else{
			add_action('init', function() {

				if( class_exists( 'WPSEO_Frontend' ) && method_exists( 'WPSEO_Frontend', 'debug_mark' ) )
					remove_action( 'wpseo_head', [\WPSEO_Frontend::get_instance(), 'debug_mark'], 2);

				add_filter('wpseo_canonical', [$this, 'filterCanonical']);

				add_filter('wp-bundle/make_link_relative', function($make){

					global $wp_query;
					return $make && empty($wp_query->query_vars["sitemap"]) && empty($wp_query->query_vars["yoast-sitemap-xsl"]);
				});

				add_filter('robots_txt', [$this, 'sitemapToRobots'], 9999, 1 );
			});
		}
	}
}