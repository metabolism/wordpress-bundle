<?php

namespace Metabolism\WordpressBundle\Plugin;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class EditorPlugin {

	private $config;


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
		if( !is_admin() and is_post_type_archive() )
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


	public function adminMenu()
	{
		foreach ( $this->config->get('remove_menu_page', []) as $menu )
		{
			remove_menu_page($menu);
		}

		remove_submenu_page('themes.php', 'themes.php' );

		foreach ( $this->config->get('remove_submenu_page', []) as $menu=>$submenu )
		{
			remove_submenu_page($menu, $submenu);
		}

		global $submenu;

		if ( isset( $submenu[ 'themes.php' ] ) )
		{
			foreach ( $submenu[ 'themes.php' ] as $index => $menu_item )
			{
				if ( in_array( 'Customize', $menu_item ) )
					unset( $submenu[ 'themes.php' ][ $index ] );
			}

			if( empty($submenu[ 'themes.php' ]) )
				remove_menu_page('themes.php');
		}
	}

	
	public function __construct($config)
	{
		$this->config = $config;

		add_action( 'wp_before_admin_bar_render', function() {
			global $wp_admin_bar;
			$wp_admin_bar->remove_menu('customize');
		} );

		if( is_admin() )
		{
			add_filter( 'mce_buttons', [$this, 'TinyMceButtons']);
			add_filter( 'wp_editor_settings', [$this, 'editorSettings'], 10, 2);
			add_action( 'admin_bar_menu', [$this, 'archiveButton']);
			add_action( 'admin_menu', [$this, 'adminMenu']);
		}
	}
}
