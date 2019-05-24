<?php

namespace Metabolism\WordpressBundle\Provider;

/**
 * Class WPSEOProvider
 *
 * @package Metabolism\WordpressBundle\Provider
 */
class WPSEOProvider
{
	/**
	 * Disable editor options for seo taxonomy edition
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
	 * Add primary flagged term in first position
	 */
	public function changeTermsOrder($terms, $postID, $taxonomy){

		if ( class_exists('WPSEO_Primary_Term') ) {

			$wpseo_primary_term = new \WPSEO_Primary_Term( $taxonomy, $postID);

			if( $wpseo_primary_term ){

				$primary_term_id = $wpseo_primary_term->get_primary_term();
				foreach ($terms as $key=>$term){

					if( $term->term_id == $primary_term_id)
						unset($terms[$key]);
				}

				$terms = array_merge([$primary_term_id], $terms);
			}
		}

		return $terms;
	}


	/**
	 * Construct
	 */
	public function __construct()
	{
		if( !class_exists( 'WPSEO' ) )
			return;

		add_action( 'admin_init', [$this, 'init'] );

		if( is_admin() ) {
			add_filter( 'wp_editor_settings', [$this, 'editorSettings'], 10, 2);
		}
		else{
			add_action('init', function() {
				if( method_exists( 'WPSEO_Frontend', 'debug_mark' ) )
					remove_action( 'wpseo_head', [\WPSEO_Frontend::get_instance(), 'debug_mark'], 2);
			});

			add_filter( 'get_the_terms', [$this, 'changeTermsOrder'], 10, 3);
		}
	}
}