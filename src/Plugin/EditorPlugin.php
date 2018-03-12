<?php

namespace Metabolism\WordpressBundle\Plugin;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class EditorPlugin {

	/**
	 * Add custom post type for taxonomy archive page
	 */
	public function editorSettings( $settings, $editor_id )
	{
		if ( $editor_id == 'description' and class_exists('WPSEO_Taxonomy') and \WPSEO_Taxonomy::is_term_edit( $GLOBALS['pagenow'] ) )
		{
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
	 * Configure Tiny MCE first line buttons
	 */
	public function TinyMceButtons( $mce_buttons )
	{
		$mce_buttons = array(
			'formatselect','bold','italic','underline','strikethrough','bullist','numlist','blockquote','hr','alignleft',
			'aligncenter','alignright','alignjustify','link','unlink','wp_more','spellchecker','wp_adv','dfw'
		);
		return $mce_buttons;
	}


	public function archiveButton($wp_admin_bar)
	{
		if( is_post_type_archive() )
		{
			$object = get_queried_object();

			$args = [
				'id'    => 'edit',
				'title' => __('Edit Posts'),
				'href'  => get_admin_url( null, '/edit.php?post_type='.$object->name ),
				'meta'   => ['class' => 'ab-item']
			];

			$wp_admin_bar->add_node( $args );
		}
	}

	
	public function __construct($config)
	{
		// When viewing admin
		if( is_admin() )
		{
			// Remove image sizes for thumbnails
			add_filter( 'mce_buttons', [$this, 'TinyMceButtons']);
			add_filter( 'wp_editor_settings', [$this, 'editorSettings'], 10, 2);
			add_action( 'admin_bar_menu', [$this, 'archiveButton']);
		}
	}
}
