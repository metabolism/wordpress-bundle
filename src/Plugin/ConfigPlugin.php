<?php

namespace Metabolism\WordpressBundle\Plugin;

use Metabolism\WordpressBundle\Helper\Table;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class ConfigPlugin {

	protected $config;

	/**
	 * Get plural from name
	 */
	public function plural($name)
	{
		return substr($name, -1) == 's' ? $name : (substr($name, -1) == 'y' ? substr($name, 0, -1).'ies' : $name.'s');
	}


	/**
	 * Adds specific post types here
	 * @see CustomPostType
	 */
	public function addPostTypes()
	{
		$default_args = [
			'public' => true,
			'has_archive' => true,
			'menu_position' => 25
		];

		$is_admin = is_admin();

		foreach ( $this->config->get('post_type', []) as $post_type => $args )
		{
			if( $post_type != 'post' && $post_type != 'page' )
			{
				$args = array_merge($default_args, $args);
				$name = str_replace('_', ' ', $post_type);

				$labels = [
					'name' => ucfirst($this->plural($name)),
					'singular_name' => ucfirst($name),
					'all_items' =>'All '.$this->plural($name),
					'edit_item' => 'Edit '.$name,
					'view_item' => 'View '.$name,
					'update_item' => 'Update '.$name,
					'add_new_item' => 'Add a new '.$name,
					'new_item_name' => 'New '.$name,
					'search_items' => 'Search in '.$this->plural($name),
					'popular_items' => 'Popular '.$this->plural($name),
					'view_items' => 'View '.$this->plural($name),
					'not_found' => ucfirst($name).' not found'
				];

				if( isset($args['labels']) )
					$args['labels'] = array_merge($labels, $args['labels']);
				else
					$args['labels'] = $labels;

				if( isset($args['menu_icon']) )
					$args['menu_icon'] = 'dashicons-'.$args['menu_icon'];

				$slug = get_option( $post_type. '_rewrite_slug' );

				if( !is_null($slug) && !empty($slug) )
					$args['rewrite'] = ['slug'=>$slug];

				if( $args['has_archive'] ){

					$archive = get_option( $post_type. '_rewrite_archive' );

					if( !is_null($archive) && !empty($archive) )
						$args['has_archive'] = $archive;
				}

				register_post_type($post_type, $args);

				if( $is_admin )
				{
					if( isset($args['columns']) )
					{
						add_filter ( 'manage_'.$post_type.'_posts_columns', function ( $columns ) use ( $args )
						{
							return array_merge ( $columns, $args['columns'] );
						});

						add_action ( 'manage_'.$post_type.'_posts_custom_column', function ( $column, $post_id ) use ( $args )
						{
							if( isset($args['columns'][$column]) )
								echo get_post_meta( $post_id, $column, true );

						}, 10, 2 );

					}

					if( isset($args['has_options']) && function_exists('acf_add_options_page') )
					{
						if( is_bool($args['has_options']) )
						{
							$args = [
								'page_title' 	=> ucfirst($name).' archive options',
								'menu_title' 	=> 'Archive options'
							];
						}

						$args['menu_slug'] = 'options_'.$post_type;
						$args['parent_slug'] = 'edit.php?post_type='.$post_type;

						acf_add_options_sub_page($args);
					}
				}

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
			if( $taxonomy != 'type' && $taxonomy != 'category' && $taxonomy != 'tag' ) {

				$args = array_merge($default_args, $args);
				$name = str_replace('_', ' ', isset($args['name']) ? $args['name'] : $taxonomy);

				$labels = [
					'name' => ucfirst($this->plural($name)),
					'all_items' => 'All ' . $this->plural($name),
					'singular_name' => ucfirst($name),
					'add_new_item' => 'Add a ' . $name,
					'edit_item' => 'Edit ' . $name,
					'not_found' => ucfirst($name) . ' not found',
					'search_items' => 'Search in ' . $this->plural($name)
				];

				$slug = get_option( $taxonomy. '_rewrite_slug' );

				if( !is_null($slug) and !empty($slug) )
					$args['rewrite'] = ['slug'=>$slug];

				if (!isset($args['hierarchical']))
					$args['hierarchical'] = true;

				if (!isset($args['show_admin_column']))
					$args['show_admin_column'] = true;

				if (isset($args['labels']))
					$args['labels'] = array_merge($labels, $args['labels']);
				else
					$args['labels'] = $labels;

				if (isset($args['object_type'])) {
					$object_type = $args['object_type'];
					unset($args['object_type']);
				} else {
					$object_type = 'post';
				}

				register_taxonomy($taxonomy, $object_type, $args);
			}
		}
	}

	/**
	 * Adds User role
	 * @see Taxonomy
	 */
	public function addRoles()
	{
		global $wp_roles;

		foreach ( $this->config->get('role', []) as $role => $args )
		{
			if( !isset($wp_roles->roles[$role]))
				add_role($role, $args['display_name'], $args['capabilities']);
		}

		$wp_roles->add_cap( 'editor', 'wpseo_edit_advanced_metadata' );
		$wp_roles->add_cap( 'editor', 'wpseo_manage_options' );
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
		$updated = false;

		add_settings_section('custom_post_type_rewrite', 'Custom post type', false,'permalink');

		foreach ( get_post_types(['public'=> true, '_builtin' => false], 'objects') as $post_type=>$args )
		{
			foreach( ['slug', 'archive'] as $type)
			{
				if( $type == 'slug' or ($type == 'archive' and $args->has_archive ))
				{
					if( isset( $_POST[$post_type. '_rewrite_'.$type] ) && !empty($_POST[$post_type. '_rewrite_'.$type]) )
					{
						update_option( $post_type. '_rewrite_'.$type, sanitize_title_with_dashes( $_POST[$post_type. '_rewrite_'.$type] ) );
						$updated = true;
					}

					add_settings_field( $post_type. '_rewrite_'.$type, __( ucfirst(str_replace('_', ' ', $post_type)).' '.$type ),function () use($post_type, $type)
					{
						$value = get_option( $post_type. '_rewrite_'.$type );
						if( is_null($value) || empty($value))
							$value = $this->config->get('post_type.'.$post_type.($type=='slug'?'.rewrite.slug':'has_archive'), $post_type);

						echo '<input type="text" value="' . esc_attr( $value ) . '" name="'.$post_type.'_rewrite_'.$type.'" placeholder="'.$post_type.'" id="'.$post_type.'_rewrite_'.$type.'" class="regular-text" />';

					}, 'permalink', 'custom_post_type_rewrite' );
				}
			}
		}

		add_settings_section('custom_taxonomy_rewrite', 'Custom taxonomy', false,'permalink');

		foreach ( get_taxonomies(['public'=> true, '_builtin' => false], 'objects') as $taxonomy=>$args )
		{
			if( isset( $_POST[$taxonomy. '_rewrite_slug'] ) && !empty($_POST[$taxonomy. '_rewrite_slug']) )
			{
				update_option( $taxonomy. '_rewrite_slug', sanitize_title_with_dashes( $_POST[$taxonomy. '_rewrite_slug'] ) );
				$updated = true;
			}

			add_settings_field( $taxonomy. '_rewrite_slug', __( ucfirst(str_replace('_', ' ', $taxonomy)) ),function () use($taxonomy)
			{
				$value = get_option( $taxonomy. '_rewrite_slug' );
				if( is_null($value) || empty($value))
					$value = $this->config->get('taxonomy.'.$taxonomy.'.rewrite.slug', $taxonomy);

				echo '<input type="text" value="' . esc_attr( $value ) . '" name="'.$taxonomy.'_rewrite_slug" placeholder="'.$taxonomy.'" id="'.$taxonomy.'_rewrite_slug" class="regular-text" />';

			}, 'permalink', 'custom_taxonomy_rewrite' );
		}


		if( $updated )
		{
			global $wp_rewrite;
			$wp_rewrite->flush_rules( true );

			do_action('purge_cache');
		}
	}


	public function addTableViews()
	{
		foreach ( $this->config->get('table', []) as $name => $args )
		{
			$default_args = [
				'page_title' => ucfirst($name),
				'menu_title' => ucfirst($name),
				'capability' => 'activate_plugins',
				'singular'   => $name,
				'menu_icon'  => 'editor-table',
				'plural'     => $this->plural($name),
				'per_page'   => 20,
				'position'   => 30,
				'export'     => true
			];

			$args = array_merge($default_args, $args);
			$args['menu_icon'] = 'dashicons-'.$args['menu_icon'];

			$table = new Table($name, $args);

			add_action('admin_menu', function() use($name, $table, $args) {

				add_menu_page($args['page_title'], $args['menu_title'], $args['capability'], 'table_'.$name, function() use($table, $args)
				{
					$table->init();
					$table->prepare_items();
					$table->display();

				}, $args['menu_icon'], $args['position']);
			});
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
			$this->addRoles();
			$this->addMenus();
			$this->setPermalink();
			
			if( is_admin() )
			{
				$this->addTableViews();
				$this->addOptionPages();
			}
		});


		// When viewing admin
		if( is_admin() )
		{
			add_action( 'load-options-permalink.php', [$this, 'LoadPermalinks']);

			$support = $this->config->get('support', []);

			if( in_array('post_thumbnails', $support ) )
				add_theme_support( 'post-thumbnails' );

			if( in_array('woocommerce', $support ) )
				add_theme_support( 'woocommerce' );

			add_post_type_support( 'page', 'excerpt' );
		}
	}
}
