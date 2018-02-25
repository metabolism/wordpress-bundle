<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Metabolism\WordpressLoader\Model;

use Dflydev\DotAccessData\Data as DotAccessData;

/**
 * Class CustomPostType
 * @package Customer
 */
class CustomPostTypeModel {


    private $option, $slug, $name;
    private static $custom_types;


    /**
     * Return a list of all created CustomPostTypes
     * @return mixed
     */
    public static function getCustomPostTypes()
    {
        return static::$custom_types;
    }


    /**
     * CustomPostType constructor.
     * @param $label
     * @param $slug
     */
    public function __construct($label, $slug)
    {
        if (empty(self::$custom_types))
        {
            self::$custom_types = array();
        }

        self::$custom_types[$slug] = false;

        $this->name = $label;

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
     * (importance) Whether to exclude posts with this post type from front end search results.
     * @param $bool
     */
    public function exclude_from_search($bool)
    {
        $this->option['exclude_from_search'] = $bool;
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
     * (boolean) (optional) Whether to make this post type available in the WordPress admin bar.
     * @param $bool bool default: value of the show_in_menu argument
     */
    public function show_in_admin_bar($bool)
    {
        $this->option['show_in_admin_bar'] = $bool;
    }

    /**
     * (integer) (optional) The position in the menu order the post type should appear. show_in_menu must be true.
     * @param $pos int default: null - defaults to below Comments
     * 5 - below Posts
     * 10 - below Media
     * 15 - below Links
     * 20 - below Pages
     * 25 - below comments
     * 60 - below first separator
     * 65 - below Plugins
     * 70 - below Users
     * 75 - below Tools
     * 80 - below Settings
     * 100 - below second separator
     */
    public function menu_position($pos)
    {
        $this->option['menu_position'] = $pos;
    }

    /**
     * @param $icon_name
     */
    public function menu_icon($icon_name)
    {
        $this->option['menu_icon'] = 'dashicons-'.$icon_name;
    }

    /**
     * (string or array) (optional) The string to use to build the read, edit, and delete capabilities. May be passed as an array to allow for alternative plurals when using this argument as a base to construct the capabilities, e.g. array('story', 'stories') the first array element will be used for the singular capabilities and the second array element for the plural capabilities, this is instead of the auto generated version if no array is given which would be "storys". The 'capability_type' parameter is used as a base to construct capabilities unless they are explicitly set with the 'capabilities' parameter. It seems that `map_meta_cap` needs to be set to false or null, to make this work (see note 2 below).
     * @param $capability_type String default: "post"
     */
    public function capability_type($capability_type)
    {
        $this->option['capability_type'] = $capability_type;
    }


    /**
     * (array) (optional) An array of the capabilities for this post type.
     * @param $capabilities array
     * edit_post, read_post, and delete_post - These three are meta capabilities, which are then generally mapped to corresponding primitive capabilities depending on the context, for example the post being edited/read/deleted and the user or role being checked. Thus these capabilities would generally not be granted directly to users or roles.
     * edit_posts - Controls whether objects of this post type can be edited.
     * edit_others_posts - Controls whether objects of this type owned by other users can be edited. If the post type does not support an author, then this will behave like edit_posts.
     * publish_posts - Controls publishing objects of this post type.
     * read_private_posts - Controls whether private objects can be read.
     * read - Controls whether objects of this post type can be read.
     * delete_posts - Controls whether objects of this post type can be deleted.
     * delete_private_posts - Controls whether private objects can be deleted.
     * delete_published_posts - Controls whether published objects can be deleted.
     * delete_others_posts - Controls whether objects owned by other users can be can be deleted. If the post type does not support an author, then this will behave like delete_posts.
     * edit_private_posts - Controls whether private objects can be edited.
     * edit_published_posts - Controls whether published objects can be edited.
     * create_posts - Controls whether new objects can be created
     */
    public function capabilities($capabilities)
    {
        $this->option['capabilities'] = $capabilities;
    }

    /**
     * (boolean) (optional) Whether to use the internal default meta capability handling.
     * @param $bool bool default: null
     */
    public function map_meta_cap($bool)
    {
        $this->option['map_meta_cap'] = $bool;
    }

    /**
     * (boolean) (optional) Whether the post type is hierarchical (e.g. page). Allows Parent to be specified. The 'supports' parameter should contain 'page-attributes' to show the parent select box on the editor page.
     * @param $bool
     */
    public function hierarchical($bool)
    {
        $this->option['hierarchical'] = $bool;
    }

    /**
     * (array/boolean) (optional) An alias for calling add_post_type_support() directly. As of 3.5, boolean false can be passed as value instead of an array to prevent default (title and editor) behavior.
     * @param $supports array default: title and editor, possible values :
     * 'title'
     * 'editor' (content)
     * 'author'
     * 'thumbnail' (featured image, current theme must also support post-thumbnails)
     * 'excerpt'
     * 'trackbacks'
     * 'custom-fields'
     * 'comments' (also will see comment count balloon on edit screen)
     * 'revisions' (will store revisions)
     * 'page-attributes' (menu order, hierarchical must be true to show Parent option)
     * 'post-formats' add post formats, see Post Formats
     */
    public function supports($supports)
    {
        $this->option['supports'] = $supports;
    }

    /**
     * (callback ) (optional) Provide a callback function that will be called when setting up the meta boxes for the edit form. The callback function takes one argument $post, which contains the WP_Post object for the currently edited post. Do remove_meta_box() and add_meta_box() calls in the callback.
     * @param $callback callable default: None
     */
    public function register_meta_box_cb($callback)
    {
        $this->option['register_meta_box_cb'] = $callback;
    }

    /**
     * (array) (optional) An array of registered taxonomies like category or post_tag that will be used with this post type. This can be used in lieu of calling register_taxonomy_for_object_type() directly. Custom taxonomies still need to be registered with register_taxonomy().
     * @param $taxonomies array default: no taxonomies
     */
    public function taxonomies($taxonomies)
    {
        $this->option['taxonomies'] = $taxonomies;
    }

    /**
     * (boolean or string) (optional) Enables post type archives. Will use $post_type as archive slug by default.
     * @param $bool bool|string default: false
     */
    public function has_archive($bool)
    {
        $this->option['has_archive'] = $bool;
    }

    /**
     * (boolean or array) (optional) Triggers the handling of rewrites for this post type. To prevent rewrites, set to false.
     * @param $args bool|string default: true and use $post_type as slug. Possible values :
     * 'slug' => string Customize the permalink structure slug. defaults to the $post_type value. Should be translatable.
     * 'with_front' => bool Should the permalink structure be prepended with the front base. (example: if your permalink structure is /blog/, then your links will be: false->/news/, true->/blog/news/). defaults to true
     * 'feeds' => bool Should a feed permalink structure be built for this post type. defaults to has_archive value.
     * 'pages' => bool Should the permalink structure provide for pagination. defaults to true
     * 'ep_mask' => const As of 3.4 Assign an endpoint mask for this post type (example). For more info see Rewrite API/add_rewrite_endpoint, and Make WordPress Plugins summary of endpoints.
     * If not specified, then it inherits from permalink_epmask(if permalink_epmask is set), otherwise defaults to EP_PERMALINK.
     */
    public function rewrite($args)
    {
        $this->option['rewrite'] = $args;
    }

    /**
     * (string) (optional) The default rewrite endpoint bitmasks. For more info see Trac Ticket 12605 and this - Make WordPress Plugins summary of endpoints.
     * @param $bitmask string default: EP_PERMALINK
     */
    public function permalink_epmask($bitmask)
    {
        $this->option['permalink_epmask'] = $bitmask;
    }

    /**
     * (boolean or string) (optional) Sets the query_var key for this post type.
     * @param $query bool|string default: true - set to $post_type. Possible values 'false' - Disables query_var key use. A post type cannot be loaded at /?{query_var}={single_post_slug}
     * 'string' - /?{query_var_string}={single_post_slug} will work as intended.
     */
    public function query_var($query)
    {
        $this->option['query_var'] = $query;
    }

    /**
     * (boolean) (optional) Can this post_type be exported.
     * @param $bool bool default: true
     */
    public function can_export($bool)
    {
        $this->option['can_export'] = $bool;
    }

    /**
     * (boolean) (optional) Whether to delete posts of this type when deleting a user. If true, posts of this type belonging to the user will be moved to trash when then user is deleted. If false, posts of this type belonging to the user will not be trashed or deleted. If not set (the default), posts are trashed if post_type_supports('author'). Otherwise posts are not trashed or deleted.
     * @param $bool bool default: null
     */
    public function delete_with_user($bool)
    {
        $this->option['delete_with_user'] = $bool;
    }

    /**
     * (boolean) (optional) Whether to expose this post type in the REST API
     * @param $bool bool default: false
     */
    public function show_in_rest($bool)
    {
        $this->option['show_in_rest'] = $bool;
    }

    /**
     * (string) (optional) The base slug that this post type will use when accessed using the REST API.
     * @param $string string default: $post_type
     */
    public function rest_base($string)
    {
        $this->option['rest_base'] = $string;
    }

    /**
     * (string) (optional) An optional custom controller to use instead of WP_REST_Posts_Controller. Must be a subclass of WP_REST_Controller.
     * @param $controller_class string default: WP_REST_Posts_Controller
     */
    public function rest_controller_class($controller_class)
    {
        $this->option['rest_controller_class'] = $controller_class;
    }

    /**
     * (bool) (optional) Whether or not register the CustomPostType in APIRest sitemap.
     * @param $bool bool showed in sitemap or not
     */
    public function show_in_sitemap($bool)
    {
        CustomPostType::$custom_types[$this->slug] = $bool;
    }

    /**
     * Callable function for action setting.
     * example : add_action( 'init', array($declaredClass, 'register') );
     */
    public function register()
    {
        register_post_type($this->slug, $this->option);
    }

    /**
     * Create default dataset for Custom Post Type according to configuration file.
     * @param $data_post_type DotAccessData Configuration data
     */
    public function hydrate($data_post_type)
    {
        $this->label_name(__($data_post_type->get('labels.name', ucfirst($this->name)), 'wordpress_loader'));
        $this->label_all_items(__($data_post_type->get('labels.all_items','All '.$this->name), 'wordpress_loader'));
        $this->label_singular_name(__($data_post_type->get('labels.singular_name',ucfirst($this->slug)), 'wordpress_loader'));
        $this->label_add_new_item(__($data_post_type->get('labels.add_new_item','Add a '.$this->slug), 'wordpress_loader'));
        $this->label_edit_item(__($data_post_type->get('labels.edit_item','Edit '.$this->slug), 'wordpress_loader'));
        $this->label_not_found(__($data_post_type->get('labels.not_found',ucfirst($this->slug).' not found'), 'wordpress_loader'));
        $this->label_search_items(__($data_post_type->get('labels.search_items','Search in '.$this->name), 'wordpress_loader'));

        $this->menu_icon($data_post_type->get('menu_icon','media-default'));
        $this->setPublic($data_post_type->get('public', true));
	    $this->publicly_queryable($data_post_type->get('publicly_queryable', true));
	    $this->has_archive($data_post_type->get('has_archive', false));
        $this->capability_type($data_post_type->get('capability_type', 'page'));
        $this->supports( $data_post_type->get('supports', ['title', 'editor', 'thumbnail']));
        $this->rewrite($data_post_type->get('rewrite', true));
        $this->exclude_from_search($data_post_type->get('exclude_from_search', false));
        $this->query_var($data_post_type->get('query_var', true));
	    $this->taxonomies($data_post_type->get('taxonomies', []));

        $this->show_in_menu($data_post_type->get('show_in_menu', true));
        $this->show_in_nav_menus($data_post_type->get('show_in_nav_menus', true));
    }
}
