<?php

namespace Metabolism\WordpressLoader\Plugin;

use Dflydev\DotAccessData\Data as DotAccessData;

use Metabolism\WordpressLoader\Model\CustomPostTypeModel as CustomPostType,
	Metabolism\WordpressLoader\Model\MenuModel as Menu,
	Metabolism\WordpressLoader\Model\TaxonomyModel as Taxonomy;

/**
 * Class Metabolism\WordpressLoader Framework
 */
class ConfigPlugin {

	protected $config, $bo_domain_name;


	/**
	 * Add settings to acf
	 */
	public function ACFInit()
	{
		acf_update_setting('google_api_key', $this->config->get('options.gmap_api_key', ''));
	}
	
	
	/**
	 * Adds or remove pages from menu admin.
	 */
	public function adminMenu()
	{
		//clean interface
		foreach ( $this->config->get('remove_menu_page', []) as $menu )
		{
			remove_menu_page($menu);
		}

		remove_submenu_page('themes.php', 'themes.php' );

		//clean interface
		foreach ( $this->config->get('remove_submenu_page', []) as $menu=>$submenu )
		{
			remove_submenu_page($menu, $submenu);
		}
	}


	/**
	 * Adds specific post types here
	 * @see CustomPostType
	 */
	public function addPostTypes()
	{
		foreach ( $this->config->get('post_types', []) as $slug => $data )
		{
			$data = new DotAccessData($data);

			$label = __(ucfirst($this->config->get('taxonomies.'.$slug.'.name', $slug.'s')), $this->bo_domain_name);

			if( $slug != 'post' )
			{
				$post_type = new CustomPostType($label, $slug);
				$post_type->hydrate($data);
				$post_type->register();
			}
		};
	}


	/**
	 * Add wordpress configuration 'options_page' fields as ACF Options pages
	 */
	protected function addOptionPages()
	{
		if( function_exists('acf_add_options_page') )
		{
			acf_add_options_page();

			foreach ( $this->config->get('options_page', []) as $name )
			{
				if( isset($name['menu_slug']) )
					$name['menu_slug'] = 'acf-options-'.$name['menu_slug'];

				acf_add_options_sub_page($name);
			}
		}
	}


	/**
	 * Create Menu instances from configs
	 * @see Menu
	 */
	public function addMenus()
	{
		foreach ($this->config->get('menus', []) as $slug => $name)
		{
			new Menu($name, $slug);
		}
	}


	/**
	 * Adds Custom taxonomies
	 * @see Taxonomy
	 */
	public function addTaxonomies()
	{
		foreach ( $this->config->get('taxonomies', []) as $slug => $data )
		{
			$data = new DotAccessData($data);
			$label = __(ucfirst( $this->config->get('taxonomies.'.$slug.'.name', $slug.'s')), $this->bo_domain_name);

			$taxonomy = new Taxonomy($label, $slug);
			$taxonomy->hydrate($data);
			$taxonomy->register();
		}
	}


	/**
	 * Set permalink stucture
	 */
	public function setPermalink()
	{
		global $wp_rewrite;

		$wp_rewrite->set_permalink_structure('/%postname%');

		update_option( 'rewrite_rules', FALSE );

		$wp_rewrite->flush_rules( true );
	}


	public function __construct($config)
	{
		$this->config = $config;

		$this->bo_domain_name = 'bo_'.$this->config->get('domain_name', 'customer');


		if( $jpeg_quality = $this->config->get('jpeg_quality') )
			add_filter( 'jpeg_quality', function() use ($jpeg_quality){ return $jpeg_quality; });

		// Global init action
		add_action( 'init', function()
		{
			$this->addPostTypes();
			$this->addTaxonomies();
			$this->addMenus();
			$this->setPermalink();

			if( is_admin() )
			{
				$this->addOptionPages();
			}
		});


		// When viewing admin
		if( is_admin() )
		{
			// Setup ACF Settings
			add_action( 'acf/init', [$this, 'ACFInit'] );

			// Removes or add pages
			add_action( 'admin_menu', [$this, 'adminMenu']);

			$theme_support = $this->config->get('theme_support', []);

			if( in_array('post_thumbnails', $theme_support ) )
				add_theme_support( 'post-thumbnails' );

			if( in_array('woocommerce', $theme_support ) )
				add_theme_support( 'woocommerce' );

			add_post_type_support( 'page', 'excerpt' );
		}
	}
}
