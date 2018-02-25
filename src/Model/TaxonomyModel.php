<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Metabolism\WordpressLoader\Model;

use Dflydev\DotAccessData\Data as DotAccessData;

/**
 * Class Taxonomy
 * @package Customer
 */
class TaxonomyModel
{
    static $defaults;
    private $name, $option, $slug, $object_types, $data;

    /**
     * Taxonomy constructor.
     * @param $name
     * @param $slug
     */
    public function __construct($name, $slug)
    {
        $this->name = $name;

        $this->option = array(
            'public' => true,
            'labels' => ['name'=>$this->name]
        );

        $this->slug = $slug;
    }

    /**
     * general name for the post type, usually plural. The same and overridden by $post_type_object->label. default is Posts/Pages
     * @param $text String Displayed text.
     */
    public function label_name($text)
    {
        $this->option['labels']['name'] = $text;
    }

    /**
     * name for one object of this post type. default is Post/Page
     * @param $text String Displayed text.
     */
    public function label_singular_name($text)
    {
        $this->option['labels']['singular_name'] = $text;
    }

    /**
     * the add new text. The default is "Add New" for both hierarchical and non-hierarchical post types. When internationalizing this string, please use a gettext context matching your post type. Example: _x('Add New', 'product');
     * @param $text String Displayed text.
     */
    public function label_add_new($text)
    {
        $this->option['labels']['add_new'] = $text;
    }

    /**
     * default is Add New Post/Add New Page.
     * @param $text String Displayed text.
     */
    public function label_add_new_item($text)
    {
        $this->option['labels']['add_new_item'] = $text;
    }

    /**
     * default is Edit Post/Edit Page.
     * @param $text String Displayed text.
     */
    public function label_edit_item($text)
    {
        $this->option['labels']['edit_item'] = $text;
    }

    /**
     * default is New Post/New Page.
     * @param $text String Displayed text.
     */
    public function label_new_item($text)
    {
        $this->option['labels']['new_item'] = $text;
    }

    /**
     * default is View Post/View Page.
     * @param $text String Displayed text.
     */
    public function label_view_item($text)
    {
        $this->option['labels']['view_item'] = $text;
    }

    /**
     * default is Search Posts/Search Pages.
     * @param $text String Displayed text.
     */
    public function label_search_items($text)
    {
        $this->option['labels']['search_items'] = $text;
    }

    /**
     * default is No posts found/No pages found.
     * @param $text String Displayed text.
     */
    public function label_not_found($text)
    {
        $this->option['labels']['not_found'] = $text;
    }

    /**
     * default is No posts found in Trash/No pages found in Trash.
     * @param $text String Displayed text.
     */
    public function label_not_found_in_trash($text)
    {
        $this->option['labels']['not_found_in_trash'] = $text;
    }

    /**
     * This string isn't used on non-hierarchical types. In hierarchical ones the default is 'Parent Page:'.
     * @param $text String Displayed text.
     */
    public function label_parent_item_colon($text)
    {
        $this->option['labels']['parent_item_colon'] = $text;
    }

    /**
     * String for the submenu. default is All Posts/All Pages.
     * @param $text String Displayed text.
     */
    public function label_all_items($text)
    {
        $this->option['labels']['all_items'] = $text;
    }

    /**
     * String for use with archives in nav menus. default is Post Archives/Page Archives.
     * @param $text String Displayed text.
     */
    public function label_archives($text)
    {
        $this->option['labels']['archives'] = $text;
    }

    /**
     * String for the media frame button. default is Insert into post/Insert into page.
     * @param $text String Displayed text.
     */
    public function label_insert_into_item($text)
    {
        $this->option['labels']['insert_into_item'] = $text;
    }

    /**
     * String for the media frame filter. default is Uploaded to this post/Uploaded to this page.
     * @param $text String Displayed text.
     */
    public function label_uploaded_to_this_item($text)
    {
        $this->option['labels']['uploaded_to_this_item'] = $text;
    }

    /**
     * default is Featured Image.
     * @param $text String Displayed text.
     */
    public function label_featured_image($text)
    {
        $this->option['labels']['featured_image'] = $text;
    }

    /**
     * default is Set featured image.
     * @param $text String Displayed text.
     */
    public function label_set_featured_image($text)
    {
        $this->option['labels']['set_featured_image'] = $text;
    }

    /**
     * default is Remove featured image.
     * @param $text String Displayed text.
     */
    public function label_remove_featured_image($text)
    {
        $this->option['labels']['remove_featured_image'] = $text;
    }

    /**
     * default is Use as featured image.
     * @param $text String Displayed text.
     */
    public function label_use_featured_image($text)
    {
        $this->option['labels']['use_featured_image'] = $text;
    }

    /**
     * default is the same as `name`.
     * @param $text String Displayed text.
     */
    public function label_menu_name($text)
    {
        $this->option['labels']['menu_name'] = $text;
    }

    /**
     * String for the table views hidden heading.
     * @param $text String Displayed text.
     */
    public function label_filter_items_list($text)
    {
        $this->option['labels']['filter_items_list'] = $text;
    }

    /**
     * String for the table pagination hidden heading.
     * @param $text String Displayed text.
     */
    public function label_items_list_navigation($text)
    {
        $this->option['labels']['items_list_navigation'] = $text;
    }

    /**
     * String for the table hidden heading.
     * @param $text String Displayed text.
     */
    public function label_items_list($text)
    {
        $this->option['labels']['items_list'] = $text;
    }

    /**
     * String for use in New in Admin menu bar. default is the same as `singular_name`.
     * @param $text String Displayed text.
     */
    public function label_name_admin_bar($text)
    {
        $this->option['labels']['name_admin_bar'] = $text;
    }

    public function setDescription($description)
    {
        $this->option['description'] = __($description, 'wordpress_loader');
    }

    /**
     * Controls how the type is visible to authors (show_in_nav_menus, show_ui) and readers (exclude_from_search, publicly_queryable).
     * @param $bool
     */
    public function setPublic($bool)
    {
        $this->option['public'] = $bool;
    }

    /**
     * (optional) Whether queries can be performed on the front end as part of parse_request().
     * @param $bool
     */
    public function publicly_queryable($bool)
    {
        $this->option['publicly_queryable'] = $bool;
    }

    /**
     *  (optional) Whether to generate a default UI for managing this post type in the admin.
     * @param $bool 'false' - do not display in the admin menu
     * 'true' - display as a top level menu
     * 'some string' - If an existing top level page such as 'tools.php' or 'edit.php?post_type=page', the post type will be placed as a sub menu of that.
     */
    public function show_ui($bool)
    {
        $this->option['show_ui'] = $bool;
    }

    /**
     * (boolean) (optional) Whether post_type is available for selection in navigation menus.
     * @param $bool
     */
    public function show_in_nav_menus($bool)
    {
        $this->option['show_in_nav_menus'] = $bool;
    }

    /**
     * (boolean or string) (optional) Where to show the post type in the admin menu. show_ui must be true.
     * @param $bool String default: value of show_ui argument
     * 'false' - do not display in the admin menu
     * 'true' - display as a top level menu
     * 'some string' - If an existing top level page such as 'tools.php' or 'edit.php?post_type=page', the post type will be placed as a sub menu of that.
     */
    public function show_in_menu($bool)
    {
        $this->option['show_in_menu'] = $bool;
    }

    /**
     * (bool) Whether to list the taxonomy in the Tag Cloud Widget controls.
     * @param $bool bool default: value of the show_in_menu argument
     */
    public function show_tagcloud($bool)
    {
        $this->option['show_tagcloud'] = $bool;
    }

    /**
     * (bool) Whether to show the taxonomy in the quick/bulk edit panel.
     * @param $bool bool default: value of the show_in_menu argument
     */
    public function show_in_quick_edit($bool)
    {
        $this->option['show_in_quick_edit'] = $bool;
    }

    /**
     * (bool) Whether to display a column for the taxonomy on its post type listing screens
     * @param $bool bool default: false.
     */
    public function show_admin_column($bool)
    {
        $this->option['show_admin_column'] = $bool;
    }

    /**
     * Define which object will be attached to the taxonomy.
     * @param $object_type array|string name of object type. Example 'posts'
     */
    public function assign_to($object_type)
    {
        if (empty($this->object_types)) {
            $this->object_types = [];
        }
        if (is_array($object_type)) {
            $this->object_types = array_merge($this->object_types, $object_type);
        } else {
            array_push($this->object_types, $object_type);
        }
    }

    /**
     * (bool) Whether the taxonomy is hierarchical.
     * @param $bool bool Default false.
     */
    public function hierarchical($bool)
    {
        $this->option['hierarchical'] = $bool;
    }

    /**
     * (array) Array of capabilities for this taxonomy.
     *
     * @params array capabilities for taxonomy :
     *  -  'manage_terms'
     *         (string) Default 'manage_categories'.
     *    - 'edit_terms'
     *          (string) Default 'manage_categories'.
     *    - 'delete_terms'
     *          (string) Default 'manage_categories'.
     *    - 'assign_terms'
     *          (string) Default 'edit_posts'.
     **/
    public function capabilities($capabilities)
    {
        $this->option['capabilities'] = $capabilities;
    }

    /**
     * (bool|callable) Provide a callback function for the meta box display. If not set, post_categories_meta_box() is used for hierarchical taxonomies, and post_tags_meta_box() is used for non-hierarchical. If false, no meta box is shown.
     * @param $callback callable default: None
     */
    public function register_meta_box_cb($callback)
    {
        $this->option['register_meta_box_cb'] = $callback;
    }

    /**
     * (bool|array) Triggers the handling of rewrites for this taxonomy.
     * @param $args bool|string Default true, using $taxonomy as slug.  To prevent rewrite, set to false. To specify rewrite rules, an array can be passed with any of these keys:
     *    - 'slug'
     *          (string) Customize the permastruct slug. Default $taxonomy key.
     *    - 'with_front'
     *          (bool) Should the permastruct be prepended with WP_Rewrite::$front. Default true.
     *    - 'hierarchical'
     *          (bool) Either hierarchical rewrite tag or not. Default false.
     *    - 'ep_mask'
     *          (int) Assign an endpoint mask. Default EP_NONE.
     **/
    public function rewrite($args)
    {
        $this->option['rewrite'] = $args;
    }

    /**
     * (string) Sets the query var key for this taxonomy.
     * @param $query bool|string Default $taxonomy key. If false, a taxonomy cannot be loaded at ?{query_var}={term_slug}. If a string, the query ?{query_var}={term_slug} will be valid.
     */
    public function query_var($query)
    {
        $this->option['query_var'] = $query;
    }

    /**
     * (callable) Works much like a hook, in that it will be called when the count is updated.
     * @param $callback callable Default _update_post_term_count() for taxonomies attached to post types, which confirms that the objects are published before counting them. Default _update_generic_term_count() for taxonomies attached to other object types, such as users.
     */
    public function update_count_callback($callback)
    {
        $this->option['update_count_callback'] = $callback;
    }

    /**
     * Callable function for action setting.
     * example : add_action( 'init', array($declaredClass, 'register') );
     */
    public function register()
    {
        register_taxonomy($this->slug, $this->object_types, $this->option);

	    if( !get_term_by( 'slug', $this->data->get('default_term', 'default'), $this->slug ) )
		    $this->insert_term(ucfirst($this->data->get('default_term', 'default')), $this->data->get('default_term', 'default'));
    }

    /**
     * Insert a term to current taxonomy
     * @param $name string term name
     * @param $slug string term slug
     * @param array $args others args parameters
     */
    public function insert_term($name, $slug, $args = []) {
        wp_insert_term(
            $name,
            $this->slug,
            array_merge(array(
                'slug' => $slug
            ), $args)
        );
    }

    /**
     * @param $slug
     * @param string $post_type
     */
    public function set_default_term($slug, $post_type = "any") {

        self::add_default_term($this->slug, $slug, $post_type);
    }

    /**
     *
     * @param $taxonomy_slug
     * @param $term_slug
     * @param string $post_type
     */
    public static function add_default_term($taxonomy_slug, $term_slug, $post_type = "any") {

        if (!isset(self::$defaults)) {
            self::$defaults = array();
            add_action( 'save_post', array(get_called_class(), '_save_post_default_terms'), 100, 2 );
        }

        $default = array(
            $post_type => array(
                $taxonomy_slug => $term_slug
            )
        );

        self::$defaults = array_merge(self::$defaults, $default);
    }

    /**
     * Define a default term
     * @param $post_id
     * @param $post
     */
    public static function _save_post_default_terms( $post_id, $post ) {
        if ( 'publish' === $post->post_status ) {

            $post_type = array_key_exists($post->post_type, self::$defaults) ? $post->post_type : "any";
            $taxonomies = get_object_taxonomies( $post->post_type );

            foreach ( (array) $taxonomies as $taxonomy ) {

                $terms = wp_get_post_terms( $post_id, $taxonomy );
                if ( empty( $terms ) && is_array(self::$defaults[$post_type]) && array_key_exists( $taxonomy, self::$defaults[$post_type] ) ) {
                    wp_set_object_terms( $post_id, self::$defaults[$post_type][$taxonomy], $taxonomy );
                }
            }
        }
    }

    /**
     * Create default dataset for Taxonomu according to configuration file.
     * @param $data_taxonomy DotAccessData Configuration data
     */
    public function hydrate($data)
    {
    	$this->data = $data;

        $this->label_name(__($data->get('labels.name', ucfirst($this->name)), 'wordpress_loader'));
        $this->label_all_items(__($data->get('labels.all_items','All '.$this->name), 'wordpress_loader'));
        $this->label_singular_name(__($data->get('labels.singular_name',ucfirst($this->slug)), 'wordpress_loader'));
        $this->label_add_new_item(__($data->get('labels.add_new_item','Add a '.$this->slug), 'wordpress_loader'));
        $this->label_edit_item(__($data->get('labels.edit_item','Edit '.$this->slug), 'wordpress_loader'));
        $this->label_not_found(__($data->get('labels.not_found',ucfirst($this->slug).' not found'), 'wordpress_loader'));
        $this->label_search_items(__($data->get('labels.search_items','Search in '.$this->name), 'wordpress_loader'));

        $this->show_admin_column($data->get('show_admin_column', true));
        $this->assign_to($data->get('object_type', 'post'));
        $this->show_in_nav_menus($data->get('show_in_nav_menus', true));
        $this->setPublic($data->get('public', true));
        $this->publicly_queryable($data->get('publicly_queryable', true));
        $this->show_ui($data->get('show_ui', true));
        $this->hierarchical($data->get('hierarchical', true));
        $this->query_var($data->get('query_var', true));

        if( $data->get('default_term', 'default') )
	        $this->set_default_term($data->get('default_term', 'default'));

        $this->rewrite($data->get('rewrite', true));
    }
}
