<?php

namespace Metabolism\WordpressBundle\Plugin;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class ConfigPlugin {

	protected $config;


	/**
	 * Add settings to acf
	 */
	public function ACFInit()
	{
		$acf_settings = $this->config->get('acf', []);

		foreach ($acf_settings as $name=>$value)
			acf_update_setting($name, $value);
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
		$default_args = [
			'public' => true,
			'has_archive' => true
		];

		foreach ( $this->config->get('post_type', []) as $post_type => $args )
		{
			if( $post_type != 'post' && $post_type != 'page' )
			{
				$args = array_merge($default_args, $args);
				$name = str_replace('_', ' ', $post_type);

				$labels = [
					'name' => ucfirst($name).'s',
					'singular_name' => ucfirst($name),
					'all_items' =>'All '.$name.'s',
					'edit_item' => 'Edit '.$name,
					'view_item' => 'View '.$name,
					'update_item' => 'Update '.$name,
					'add_new_item' => 'Add a new '.$name,
					'new_item_name' => 'New '.$name,
					'search_items' => 'Search '.$name,
					'popular_items' => 'Populars '.$name,
					'not_found' => ucfirst($name).' not found'
				];

				if( isset($args['labels']) )
					$args['labels'] = array_merge($labels, $args['labels']);
				else
					$args['labels'] = $labels;

				if( isset($args['menu_icon']) )
					$args['menu_icon'] = 'dashicons-'.$args['menu_icon'];

				$slug = get_option( $post_type. '_rewrite_slug' );
				$archive = get_option( $post_type. '_rewrite_archive' );

				if( !is_null($slug) and !empty($slug) )
					$args['rewrite'] = ['slug'=>$slug];

				if( !is_null($archive) and !empty($archive) )
					$args['has_archive'] = $archive;

				register_post_type($post_type, $args);
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
		foreach ($this->config->get('menu', []) as $location => $description)
		{
			register_nav_menu($location, __($description, 'wordpress-bundle'));
		}
	}


	/**
	 * Adds Custom taxonomies
	 * @see Taxonomy
	 */
	public function addTaxonomies()
	{
		$default_args = [
			'public' => true
		];

		foreach ( $this->config->get('taxonomy', []) as $taxonomy => $args )
		{
			$args = array_merge($default_args, $args);
			$name = str_replace('_', ' ', $taxonomy);

			$labels = [
				'name' => ucfirst($name),
				'all_items' => 'All '.$name.'s',
				'singular_name' => ucfirst($name),
				'add_new_item' => 'Add a '.$name,
				'edit_item' => 'Edit '.$name,
				'not_found' => ucfirst($name).' not found',
				'search_items' => 'Search in '.$name.'s'
			];

			if( isset($args['labels']) )
				$args['labels'] = array_merge($labels, $args['labels']);
			else
				$args['labels'] = $labels;

			if( isset($args['object_type']) )
			{
				$object_type = $args['object_type'];
				unset($args['object_type']);
			}
			else
			{
				$object_type = 'post';
			}

			register_taxonomy($taxonomy, $object_type, $args);
		}
	}


	/**
	 * Set permalink stucture
	 */
	public function setPermalink()
	{
		global $wp_rewrite;

		$wp_rewrite->set_permalink_structure($this->config->get('permalink_structure', '/%postname%'));

		update_option( 'rewrite_rules', FALSE );

		$wp_rewrite->flush_rules( true );
	}


	public function LoadPermalinks()
	{
		foreach ( $this->config->get('post_type', []) as $post_type => $args )
		{
			foreach( ['slug', 'archive'] as $type)
			{
				if( $type == 'slug' or $this->config->get('post_type.'.$post_type.'has_archive') )
				{
					if( isset( $_POST[$post_type. '_rewrite_'.$type] ) )
						update_option( $post_type. '_rewrite_'.$type, sanitize_title_with_dashes( $_POST[$post_type. '_rewrite_'.$type] ) );

					add_settings_field( $post_type. '_rewrite_'.$type, __( ucfirst($post_type).' '.$type ),function () use($post_type, $type)
					{
						$value = get_option( $post_type. '_rewrite_'.$type );
						if( is_null($value) || empty($value))
							$value = $this->config->get('post_type.'.$post_type.($type=='slug'?'.rewrite.slug':'has_archive'), $post_type);

						echo '<input type="text" value="' . esc_attr( $value ) . '" name="'.$post_type.'_rewrite_'.$type.'" placeholder="'.$post_type.'" id="'.$post_type.'_rewrite_'.$type.'" class="regular-text" />';

					}, 'permalink', 'optional' );
				}
			}
		}
	}


	public function __construct($config)
	{

		$this->config = $config;

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

			add_action( 'load-options-permalink.php', [$this, 'LoadPermalinks']);

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
