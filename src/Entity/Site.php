<?php

namespace Metabolism\WordpressBundle\Entity;

use lloc\Msls\MslsOptions;

/**
 * Class User
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Site extends Entity
{
	public $entity = 'site';

    public $debug;
    public $environment;
    public $locale;
    public $is_admin;
    public $language;
    public $is_front_page;
    public $is_customize_preview;
    public $is_single;
    public $is_tax;
    public $is_archive;
    public $paged;
    public $languages;
    public $maintenance_mode;
    public $options;

    protected $domain;
    protected $breadcrumb;
    protected $pagination;
    protected $version;
    protected $home_url;
    protected $network_home_url;
    protected $search_url;
    protected $privacy_policy_url;
    protected $posts_per_page;
    protected $bloginfo;
    protected $title;
    protected $body_class;
    protected $menus;

    private $queried_object;

    public function __toString()
    {
        return $this->getTitle();
    }

	/**
	 * Site constructor.
	 *
	 */
	public function __construct()
	{
        global $wp_query;
        $this->queried_object = $wp_query->get_queried_object();

        $this->ID = get_current_blog_id();
        $this->debug = WP_DEBUG;
        $this->environment = WP_ENV;
        $this->paged = max(1, get_query_var('paged', 0));
        $this->maintenance_mode = function_exists('wp_maintenance_mode') ? wp_maintenance_mode() : false;

        $this->is_admin = current_user_can('manage_options');
        $this->is_customize_preview = is_customize_preview();
        $this->is_front_page = is_front_page();
        $this->is_single = isset($this->queried_object->post_type)??false;
        $this->is_tax = isset($this->queried_object->taxonomy)??false;
        $this->is_archive = is_object($this->queried_object) && get_class($this->queried_object) == 'WP_Post_Type' ? $this->queried_object->name : false;

        $this->languages = $this->getLanguages();
        $this->language = get_bloginfo('language');
        $language  = explode('-', $this->language);
        $this->locale = count($language) ? $language[0] : 'en';

        $this->loadMetafields('options', 'site');

        $this->options = $this->metafields;
	}

    public function getVersion(){

        if(is_null($this->version) && file_exists(BASE_URI.'/composer.json')){

            $composer = json_decode(file_get_contents(BASE_URI.'/composer.json'), true);
            $this->version = $composer['version'];
        }

        return $this->version;
    }

    public function getMenu($location=false){

        if( !$location )
            return false;

        if(is_null($this->menus) || !isset($this->menus[$location]))
            $this->menus[$location] = new Menu($location);

        return $this->menus[$location];
    }

    /**
     * @deprecated
     *
     * @return string
     */
    public function getWpTitle(){

        trigger_error('Method ' . __METHOD__ . ' is deprecated use getTitle instead', E_USER_DEPRECATED);

        return $this->getTitle();
    }

    public function getTitle(){

        if(is_null($this->title) ){

            $wp_title = trim(@wp_title(' ', false));
            $this->title = html_entity_decode(empty($wp_title) ? get_the_title( get_option('page_on_front') ) : $wp_title);
        }

        return $this->title;
    }

    public function getBodyClass(){

        if(is_null($this->body_class) ){

            $body_class = $this->queried_object ? implode(' ', get_body_class()) : '';
            $this->body_class = $this->language . ' ' . $body_class;
        }

        return $this->body_class;
    }

    public function getHomeUrl(){

        if(is_null($this->home_url) )
            $this->home_url = home_url('/');

        return $this->home_url;
    }

    public function getSearchUrl(){

        if(is_null($this->search_url) )
            $this->search_url = get_search_link();

        return $this->search_url;
    }

    public function getPrivacyPolicyUrl(){

        if(is_null($this->privacy_policy_url) )
            $this->privacy_policy_url = get_privacy_policy_url();

        return $this->privacy_policy_url;
    }

    public function getNetworkHomeUrl(){

        if(is_null($this->network_home_url) )
            $this->network_home_url = trim(network_home_url(), '/');

        return $this->network_home_url;
    }

    public function getBloginfo($name=false){

        if(is_null($this->bloginfo) || !isset($this->bloginfo[$name]))
            $this->bloginfo[$name] = get_bloginfo($name);

        return $this->bloginfo[$name];
    }

    public function getPostsPerPage(){

        if(is_null($this->posts_per_page) )
            $this->posts_per_page = intval(get_option( 'posts_per_page' ));

        return $this->posts_per_page;
    }

    public function getDomain(){

        if(is_null($this->domain) )
            $this->domain = strtok(preg_replace('/https?:\/\//', '', home_url('')),':');

        return $this->domain;
    }

    /**
     * Get multisite multilingual data
     * @return array|false
     */
    protected function getLanguages(){

        if( !is_multisite() )
            return false;

        $languages = [];

        if( defined('ICL_LANGUAGE_CODE') )
        {
            $languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=desc' );
        }
        elseif( defined('MSLS_PLUGIN_VERSION') && is_multisite() )
        {
            $sites = get_sites(['public'=>1]);
            $current_blog_id = get_current_blog_id();

            if( !function_exists('format_code_lang') )
                require_once(ABSPATH . 'wp-admin/includes/ms.php');

            $mslsOptions = MslsOptions::create();

            foreach($sites as $site)
            {
                $locale    = get_blog_option($site->blog_id, 'WPLANG');
                $locale    = empty($locale)? 'en_US' : $locale;
                $lang      = explode('_', $locale)[0];

                $alternate = $current_blog_id != $site->blog_id ? $this->getAlternativeLink($mslsOptions, $site, $locale) : false;

                $languages[] = [
                    'id' => $site->blog_id,
                    'active' => $current_blog_id==$site->blog_id,
                    'name' => format_code_lang($lang),
                    'home_url'      => get_home_url($site->blog_id, '/'),
                    'language_code' => $lang,
                    'url'           => $alternate
                ];
            }
        }

        return $languages;
    }


    /**
     * Return function echo
     * @param MslsOptions $mslsOptions
     * @param \WP_Site $site
     * @param string $locale
     * @return string
     */
    protected function getAlternativeLink($mslsOptions, $site, $locale)
    {
        switch_to_blog($site->blog_id);

        if ( MslsOptions::class != get_class( $mslsOptions ) && ( is_null( $mslsOptions ) || ! $mslsOptions->has_value( $locale ) ) ) {
            restore_current_blog();
            return false;
        }

        $alternate = $mslsOptions->get_permalink( $locale );

        restore_current_blog();

        return $alternate;
    }
}
