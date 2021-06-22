<?php

namespace Metabolism\WordpressBundle\Plugin;

use Dflydev\DotAccessData\Data;
use Metabolism\WordpressBundle\Helper\TableHelper;
use Metabolism\WordpressBundle\Traits\SingletonTrait;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class ConfigPlugin {

    use SingletonTrait;

    protected $config;
    protected $support;

    /**
     * Get plural from name
     * @param $name
     * @return string
     */
    public function plural($name)
    {
        return substr($name, -1) == 's' ? $name : (substr($name, -1) == 'y' && !in_array(substr($name, -2, 1), ['a','e','i','o','u']) ? substr($name, 0, -1).'ies' : $name.'s');
    }


    /**
     * Reload configuration
     */
    public function reload()
    {
        $this->addPostTypes();
        $this->addTaxonomies();
        $this->addRewriteRules();
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
            'supports' => [],
            'menu_position' => 25,
            'map_meta_cap' => true
        ];

        $is_admin = is_admin();

        $current_blog_id = get_current_blog_id();

        foreach ( $this->config->get('post_type', []) as $post_type => $args )
        {
            if( !in_array($post_type, ['post', 'page', 'edition']) )
            {
                if( (isset($args['enable_for_blogs']) && !in_array($current_blog_id, (array)$args['enable_for_blogs'])) || (isset($args['disable_for_blogs']) && in_array($current_blog_id, (array)$args['disable_for_blogs'])))
                    continue;

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

                if( !isset($args['capability_type']) && $args['map_meta_cap'] )
                    $args['capability_type'] = [$post_type, $this->plural($post_type)];

                $slug = $this->getSlug( $post_type );

                if( !is_null($slug) && !empty($slug) )
                    $args['rewrite'] = ['slug'=>$slug];

                if( $args['has_archive'] ){

                    $archive = get_option( $post_type. '_rewrite_archive' );

                    if( !is_null($archive) && !empty($archive) )
                        $args['has_archive'] = $archive;
                }

                if( HEADLESS && !URL_MAPPING ){

                    $args['publicly_queryable'] = false;
                }
                else{

                    preg_match_all('/{.+?}/', $slug, $toks);

                    if( count($toks) && count($toks[0]) ){

                        $rule = '^'.$slug.'/([^/]+)/?$';

                        foreach ($toks[0] as $tok)
                            $rule = str_replace($tok, '[^/]+', $rule);

                        add_rewrite_rule($rule, 'index.php?'.$post_type.'=$matches[1]', 'top');
                    }
                }

                if( isset($args['publicly_queryable']) && !$args['publicly_queryable'] ){

                    $args['query_var'] = false;
                    $args['exclude_from_search'] = false;
                    $args['rewrite'] = false;
                }

                register_post_type($post_type, $args);

                if( $is_admin )
                {
                    if( isset($args['columns']) )
                    {
                        $columns_arr = [];

                        foreach ( $args['columns'] as $id=>$column ){

                            if( is_int($id) )
                                $columns_arr[$column] = ucfirst(str_replace('_', ' ', $column));
                            else
                                $columns_arr[$id] = ucfirst(str_replace('_', ' ', $id));
                        }

                        $args['custom_columns'] = $columns_arr;

                        add_filter ( 'manage_'.$post_type.'_posts_columns', function ( $columns ) use ( $args )
                        {
                            $columns = array_merge ( $columns, $args['custom_columns']);

                            if( isset($columns['date']) ){
                                $date = $columns['date'];
                                unset($columns['date']);
                                $columns['date'] = $date;
                            }

                            return $columns;
                        });

                        add_action ( 'manage_'.$post_type.'_posts_custom_column', function ( $column, $post_id ) use ( $args )
                        {
                            if( isset($args['custom_columns'][$column]) )
                            {
                                if( $column == 'thumbnail'){

                                    if( in_array('thumbnail', $args['supports']) ){

                                        $thumbnail = get_the_post_thumbnail($post_id, 'thumbnail');
                                        echo '<a class="attachment-thumbnail-container">'.$thumbnail.$thumbnail.'</a>';
                                    }
                                    else{

                                        $thumbnail_id = get_post_meta( $post_id, 'thumbnail', true );
                                        $image = wp_get_attachment_image_src($thumbnail_id, 'thumbnail');

                                        if( $image && count($image) )
                                            echo '<a class="attachment-thumbnail-container"><img class="attachment-thumbnail size-thumbnail wp-post-image" src="'.$image[0].'"><img class="attachment-thumbnail size-thumbnail wp-post-image" src="'.$image[0].'"></a>';
                                    }
                                }
                                else{

                                    $params = $args['columns'][$column]??'';
                                    $value = get_post_meta( $post_id, $column, true );

                                    if( $params == 'bool' ){

                                        $value = boolval($value)?'☑':0;
                                        $params = '';
                                    }
                                    elseif( $value && is_numeric($value) )
                                        $value = str_replace(',00', '', number_format($value, 2, ',', ' '));

                                    if( $value )
                                        echo $value.' '.$params;
                                }
                            }
                        }, 10, 2 );
                    }

                    if( isset($args['has_options']) && function_exists('acf_add_options_sub_page') ) {

                        if( is_bool($args['has_options']) ) {

                            $args = [
                                'page_title' 	=> ucfirst($name).' archive options',
                                'menu_title' 	=> __('Archive options'),
                                'autoload'   	=> true
                            ];
                        }

                        $args['menu_slug']   = 'options_'.$post_type;
                        $args['parent_slug'] = 'edit.php?post_type='.$post_type;

                        acf_add_options_sub_page($args);
                    }
                }

            }else{

                wp_die($post_type. ' is not allowed, reserved keyword');
            }
        }

        $roles = array('editor','administrator');

        // Loop through each role and assign capabilities
        foreach($roles as $the_role) {

            if( !$role = get_role($the_role) )
                continue;

            foreach ( $this->config->get('post_type', []) as $post_type => $args ){

                if( (!isset($args['map_meta_cap']) || $args['map_meta_cap']) && (!isset($args['capability_type']) || ($args['capability_type'] != 'page' && $args['capability_type'] != 'post'))){

                    $post_types = $this->plural($post_type);

                    $role->add_cap( 'read_'.$post_type);
                    $role->add_cap( 'read_private_'.$post_types );
                    $role->add_cap( 'edit_'.$post_type );
                    $role->add_cap( 'edit_'.$post_types );
                    $role->add_cap( 'edit_others_'.$post_types );
                    $role->add_cap( 'edit_published_'.$post_types );
                    $role->add_cap( 'publish_'.$post_types );
                    $role->add_cap( 'delete_others_'.$post_types );
                    $role->add_cap( 'delete_private_'.$post_types );
                    $role->add_cap( 'delete_published_'.$post_types );
                }
            }
        }
    }


    /**
     * Register menus
     * @see Menu
     */
    public function addMenus()
    {
        $version = $this->config->get('version', 0);
        $config = $version>=1?'menu.register':'menu';

        foreach ($this->config->get($config, []) as $location => $description)
        {
            register_nav_menu($location, __($description, 'wordpress-bundle'));
        }
    }


    /**
     * Register sidebars
     * @see Menu
     */
    public function addSidebars()
    {
        foreach ($this->config->get('sidebar', []) as $id => $params)
        {
            $params['id'] = $id;
            register_sidebar($params);
        }
    }

    /**
     * Preprocess slug
     */
    private function getSlug($entity){

        $slug = get_option( $entity. '_rewrite_slug' );

        $slug = preg_replace('/^%/', '{', preg_replace('/%$/', '}', $slug));
        $slug = preg_replace('/%\//', '}/', preg_replace('/\/%/', '/{', $slug));

        return $slug;
    }


    /**
     * Adds Custom taxonomies
     * @see Taxonomy
     */
    public function addTaxonomies(){

        $default_args = [
            'public' => true
        ];

        foreach ( $this->config->get('taxonomy', []) as $taxonomy => $args ) {

            if( !in_array($taxonomy, ['category', 'tag', 'edition', 'theme']) ) {

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

                $slug = $this->getSlug( $taxonomy );

                if( !is_null($slug) && !empty($slug) )
                    $args['rewrite'] = ['slug'=>$slug];

                if (!isset($args['hierarchical']))
                    $args['hierarchical'] = true;

                if (!isset($args['capabilities'])){

                    $taxonomies = $this->plural($taxonomy);
                    $args['capabilities'] = [
                        'manage_terms' => 'manage_'.$taxonomies,
                        'edit_terms' => 'edit_'.$taxonomies,
                        'delete_terms' => 'delete_'.$taxonomies,
                        'assign_terms' => 'assign_'.$taxonomies
                    ];
                }

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

                if( HEADLESS && !URL_MAPPING )
                    $args['publicly_queryable'] = false;

                if( isset($args['publicly_queryable']) && !$args['publicly_queryable'] ){

                    $args['query_var'] = false;
                    $args['rewrite'] = false;
                }

                register_taxonomy($taxonomy, $object_type, $args);

            } else{

                wp_die($taxonomy. ' is not allowed, reserved keyword');
            }
        }

        $roles = array('editor','administrator');

        // Loop through each role and assign capabilities
        foreach($roles as $the_role) {

            $role = get_role($the_role);

            foreach ( $this->config->get('taxonomy', []) as $taxonomy => $args ){

                if( !isset($args['capabilities']) ){

                    $taxonomies = $this->plural($taxonomy);

                    $role->add_cap( 'manage_'.$taxonomies);
                    $role->add_cap( 'edit_'.$taxonomies );
                    $role->add_cap( 'delete_'.$taxonomies );
                    $role->add_cap( 'assign_'.$taxonomies );
                }
                else{

                    foreach ($args['capabilities'] as $type=>$capability){

                        $role->add_cap( $capability );
                    }
                }
            }
        }
    }

    private function getSlugs($taxonomy){

        $taxonomy = str_replace('{', '', str_replace('}', '', $taxonomy));
        $terms = get_terms($taxonomy);

        $slugs = [];

        foreach ($terms as $term)
            $slugs[] = $term->slug;

        return $slugs;
    }

    public function addRewriteRules(){

        if( HEADLESS )
            return;

        foreach ( $this->config->get('taxonomy', []) as $taxonomy => $args )
        {
            $slug = $this->getSlug( $taxonomy );

            if( $slug === '{empty}'){

                $terms = get_terms($taxonomy);
                $slugs = [];

                foreach ($terms as $term)
                    $slugs[] = $term->slug;

                add_rewrite_rule('^('.implode('|', $slugs).')$', 'index.php?'.$taxonomy.'=$matches[1]', 'top');
            }
            else{

                preg_match_all('/\/{.+?}/', $slug, $toks);

                if( count($toks) && count($toks[0]) ){

                    $rule = '^'.$slug.'/([^/]+)/?$';
                    $has_parent = false;

                    foreach ($toks[0] as $tok){

                        $has_parent = $has_parent || $tok == '/{parent}';

                        if( $tok != '/{parent}' )
                            $rule = str_replace($tok, '/[^/]+', $rule);
                    }

                    if( $has_parent ){

                        add_rewrite_rule(str_replace('/{parent}', '/[^/]+', $rule), 'index.php?'.$taxonomy.'=$matches[1]', 'top');
                        add_rewrite_rule(str_replace('/{parent}', '', $rule), 'index.php?'.$taxonomy.'=$matches[1]', 'top');
                    }
                    else{

                        add_rewrite_rule($rule, 'index.php?'.$taxonomy.'=$matches[1]', 'top');
                    }
                }
            }
        }

        foreach ( $this->config->get('post_type', []) as $post_type => $args )
        {
            $slug = $this->getSlug( $post_type );

            preg_match_all('/{.+?}/', $slug, $toks);

            if( count($toks) && count($toks[0]) ){

                $rule = '^'.$slug.'/([^/]+)/?$';

                foreach ($toks[0] as $i=>$tok){

                    if( substr($slug, 0, 1) == '{' && $i==0 ){

                        $slugs = $this->getSlugs($tok);
                        $rule = str_replace($tok, '('.implode('|', $slugs).')', $rule);
                    }
                    else{

                        $rule = str_replace($tok, '[^/]+', $rule);
                    }
                }

                add_rewrite_rule($rule, 'index.php?'.$post_type.'=$matches[1]', 'top');
            }
        }
    }

    /**
     * Adds User role
     * @see Taxonomy
     * see https://codex.wordpress.org/Function_Reference/add_role
     */
    public function addRoles()
    {
        foreach ( $this->config->get('role', []) as $role => $args )
        {
            if( is_admin() && isset($_GET['reload_role']) && $_GET['reload_role'] )
                remove_role($role);

            if( isset($args['inherit']) && !empty($args['inherit']) ){

                $inherited_role = get_role( $args['inherit'] );
                $args['capabilities'] = array_merge($inherited_role->capabilities, $args['capabilities']);
            }

            add_role($role, $args['display_name'], $args['capabilities']);
        }

        add_filter( 'editable_roles', function($all_roles){

            if( !current_user_can('administrator') )
                unset($all_roles['administrator']);

            return $all_roles;
        });
    }


    /**
     * Set permalink structure
     */
    public function setPermalink()
    {
        global $wp_rewrite;

        $wp_rewrite->set_permalink_structure($this->config->get('permalink_structure', '/%postname%'));

        update_option( 'rewrite_rules', FALSE );
    }


    public function LoadPermalinks()
    {
        $updated = false;

        add_settings_section('search_rewrite', '', false,'permalink');

        if( isset( $_POST['search_rewrite_slug'] ) && !empty($_POST['search_rewrite_slug']) )
        {
            update_option( 'search_rewrite_slug', sanitize_title_with_dashes( $_POST['search_rewrite_slug'] ) );
            $updated = true;
        }

        add_settings_field( 'search_rewrite_slug', 'Search',function ()
        {
            $value = get_option( 'search_rewrite_slug' );
            echo '<input type="text" value="' . esc_attr( $value ) . '" name="search_rewrite_slug" placeholder="search" id="search_rewrite_slug" class="regular-text" />';

        }, 'permalink', 'search_rewrite' );

        add_settings_section('custom_post_type_rewrite', 'Custom post type', false,'permalink');

        foreach ( get_post_types(['public'=> true, '_builtin' => false], 'objects') as $post_type=>$args )
        {
            foreach( ['slug', 'archive'] as $type)
            {
                if( $type == 'slug' || ($type == 'archive' && $args->has_archive ))
                {
                    if( isset( $_POST[$post_type. '_rewrite_'.$type] ) && !empty($_POST[$post_type. '_rewrite_'.$type]) )
                    {
                        update_option( $post_type. '_rewrite_'.$type, $_POST[$post_type. '_rewrite_'.$type] );
                        $updated = true;
                    }

                    add_settings_field( $post_type. '_rewrite_'.$type, __( ucfirst(str_replace('_', ' ', $post_type)).' '.$type ),function () use($post_type, $type)
                    {
                        $value = get_option( $post_type. '_rewrite_'.$type );
                        if( is_null($value) || empty($value))
                            $value = $this->config->get('post_type.'.$post_type.($type=='slug'?'.rewrite.slug':'has_archive'), $post_type);

                        echo '<input type="text" value="' . esc_attr( $value ) . '" name="'.$post_type.'_rewrite_'.$type.'" placeholder="'.$post_type.'" id="'.$post_type.'_rewrite_'.$type.'" class="regular-text" />';

                        if( $type == 'slug' ){

                            $taxonomy_objects = get_object_taxonomies( $post_type );
                            if( !empty($taxonomy_objects) )
                                echo '<p class="description">You can add %'.implode('%, %', $taxonomy_objects).'%</p>';
                        }

                    }, 'permalink', 'custom_post_type_rewrite' );
                }
            }
        }

        add_settings_section('custom_taxonomy_rewrite', 'Custom taxonomy', false,'permalink');

        foreach ( get_taxonomies(['public'=> true, '_builtin' => false], 'objects') as $taxonomy=>$args )
        {
            if( isset( $_POST[$taxonomy. '_rewrite_slug'] ) && !empty($_POST[$taxonomy. '_rewrite_slug']) )
            {
                update_option( $taxonomy. '_rewrite_slug', $_POST[$taxonomy. '_rewrite_slug'] );
                $updated = true;
            }

            add_settings_field( $taxonomy. '_rewrite_slug', __( ucfirst(str_replace('_', ' ', $taxonomy)) ),function () use($taxonomy)
            {
                $value = get_option( $taxonomy. '_rewrite_slug' );
                if( is_null($value) || empty($value))
                    $value = $this->config->get('taxonomy.'.$taxonomy.'.rewrite.slug', $taxonomy);

                echo '<input type="text" value="' . esc_attr( $value ) . '" name="'.$taxonomy.'_rewrite_slug" placeholder="'.$taxonomy.'" id="'.$taxonomy.'_rewrite_slug" class="regular-text" />';
                echo '<p class="description">You can add %parent% or use %empty%</p>';

            }, 'permalink', 'custom_taxonomy_rewrite' );
        }


        if( $updated )
            do_action('reset_cache');
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

            $table = new TableHelper($name, $args);

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


    public function loadJS(){

        echo '<script>jQuery(document).ready(function(jQuery){';

        foreach ( $this->config->get('taxonomy', []) as $taxonomy => $args )
        {
            if( isset($args['radio']) && $args['radio'] ){

                echo 'jQuery(\'#taxonomy-'.$taxonomy.' [name="tax_input['.$taxonomy.'][]"][type="checkbox"]\').attr("type", "radio");';
            }
        }

        echo '})</script>';
    }


    /**
     * Disable category
     */
    public function disableFeatures()
    {
        if( !in_array('post', $this->support) )
            register_post_type('post', []);

        if( !in_array('page', $this->support) )
            register_post_type('page', []);

        if( !in_array('category', $this->support) )
            register_taxonomy( 'category', array() );

        if( !in_array('tag', $this->support) )
            register_taxonomy( 'post_tag', array() );
    }


    /**
     * Update permalink if structure is custom
     * @param $post_link
     * @param $post
     * @return string|string[]
     */
    public  function updatePostTypePermalink($post_link, $post){

        if ( is_object( $post ) ){

            preg_match_all('/\/{.+?}/', $post_link, $toks);

            if( count($toks) && count($toks[0]) ){

                foreach ($toks[0] as $tok){

                    $taxonomy = str_replace('}', '', str_replace('/{', '', $tok));

                    $terms = get_the_terms( $post, $taxonomy );

                    if( is_array($terms) && count($terms) && is_object($terms[0]) )
                        $post_link = str_replace( '{'.$taxonomy.'}', $terms[0]->slug, $post_link );
                    else
                        $post_link = str_replace( '{'.$taxonomy.'}', 'default', $post_link );
                }
            }
        }

        return $post_link;
    }


    /**
     * Update permalink if structure is custom
     * @param $term_link
     * @param $term
     * @return string|string[]
     */
    public  function updateTermPermalink($term_link, $term){

        if ( is_object( $term ) ){

            preg_match_all('/\/{.+?}/', $term_link, $toks);

            if( count($toks) ){

                foreach ($toks[0] as $tok){

                    $match = str_replace('}', '', str_replace('/{', '', $tok));

                    if( $match == 'parent' ){

                        if( $term->parent ){
                            $parent_term = get_term($term->parent, $term->taxonomy);

                            if( $parent_term )
                                $term_link = str_replace( '{'.$match.'}', $parent_term->slug, $term_link );
                        }
                    }

                    $term_link = str_replace( '/{'.$match.'}', '', $term_link );
                }
            }
        }

        return $term_link;

    }


    /**
     * Add theme support
     */
    public function addThemeSupport()
    {
        $excluded = ['template', 'page', 'post', 'tag', 'category'];

        foreach ($this->support as $feature){

            if( $feature == 'post_thumbnails' || $feature == 'post_thumbnail' || $feature == 'thumbnail')
                $feature = 'post-thumbnails';

            if( is_array($feature) && !empty($feature) ){

                $key = array_keys($feature)[0];
                $params = $feature[$key];

                if( !in_array($key, $excluded) )
                    add_theme_support( $key, $params);
            }
            elseif( !in_array($feature, $excluded) ){

                @add_theme_support( $feature );
            }
        }
    }


    /**
     * ConfigPlugin constructor.
     * @param Data $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->support = $config->get('support', []);

        if( $jpeg_quality = $this->config->get('jpeg_quality', false) )
            add_filter( 'jpeg_quality', function() use ($jpeg_quality){ return $jpeg_quality; });

        // Global init action
        add_action( 'init', function()
        {
            $this->disableFeatures();

            $this->addPostTypes();
            $this->addTaxonomies();
            $this->addRewriteRules();

            $this->addMenus();
            $this->addSidebars();
            $this->addRoles();
            $this->addThemeSupport();

            load_theme_textdomain( $this->config->get('domain_name', 'app'), BASE_URI. '/translations' );

            if( !HEADLESS || URL_MAPPING ){

                $this->setPermalink();

                add_filter( 'post_type_link', [$this, 'updatePostTypePermalink'], 10, 2);
                add_filter( 'term_link', [$this, 'updateTermPermalink'], 10, 2);
            }

            if( is_admin() ){

                $this->addTableViews();

                if( $editor_style = $this->config->get('editor_style', false) )
                    add_editor_style( $editor_style );
            }
        });

        add_action('switch_blog', [$this, 'reload']);

        // When viewing admin
        if( is_admin() )
        {
            if( !HEADLESS || URL_MAPPING )
                add_action( 'load-options-permalink.php', [$this, 'LoadPermalinks']);

            add_action('admin_head', [$this, 'loadJS']);
        }
    }
}
