<?php

namespace Metabolism\WordpressBundle\Entity;

use lloc\Msls\MslsOptions;
use Metabolism\WordpressBundle\Factory\Factory;
use Metabolism\WordpressBundle\Helper\ClassHelper;
use Metabolism\WordpressBundle\Helper\FunctionHelper;
use Metabolism\WordpressBundle\Helper\OptionsHelper;
use Metabolism\WordpressBundle\Repository\PostRepository;
use Metabolism\WordpressBundle\Repository\TermRepository;
use Metabolism\WordpressBundle\Repository\UserRepository;
use Metabolism\WordpressBundle\Service\BreadcrumbService;
use Metabolism\WordpressBundle\Service\PaginationService;
use Metabolism\WordpressBundle\Traits\SingletonTrait;
use Twig\Environment;

/**
 * Class Blog
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Blog extends Entity
{
    use SingletonTrait;

	public $entity = 'blog';

    protected $debug;
    protected $environment;
    protected $locale;
    protected $charset;
    protected $description;
    protected $is_admin;
    protected $language;
    protected $is_front_page;
    protected $is_customize_preview;
    protected $is_single;
    protected $is_tax;
    protected $is_archive;
    protected $paged;
    protected $languages;
    protected $maintenance_mode;
    protected $options;
    protected $domain;
    protected $breadcrumb;
    protected $pagination;
    protected $version;
    protected $home_url;
    protected $network_home_url;
    protected $search_url;
    protected $privacy_policy_url;
    protected $privacy_policy_title;
    protected $user;
    protected $posts_per_page;
    protected $info;
    protected $title;
    protected $body_class;
    protected $menu;

    private $queried_object;

    public function __toString(): string
    {
        return $this->getTitle();
    }

	/**
	 * Blog constructor.
	 *
	 */
	public function __construct()
	{
        $this->ID = get_current_blog_id();
		$this->options = new OptionsHelper();

		$this->loadMetafields('options', 'blog');
	}

	/**
	 * @return \WP_Term|\WP_Post_Type|\WP_Post|\WP_User|null The queried object.
	 */
	public function getQueriedObject(){

		if( is_null($this->queried_object) ){

			global $wp_query;
			$this->queried_object = $wp_query->get_queried_object();
		}

		return $this->queried_object;
	}

	/**
	 * @param $criteria
	 * @param array|null $orderBy
	 * @param $limit
	 * @param $offset
	 * @return UserCollection
	 */
	public function getUsers($criteria=[], array $orderBy = null, $limit = null, $offset = null){

		if( is_string($criteria) )
			$criteria = ['role'=>$criteria];

		$userRepository = new UserRepository();

		return $userRepository->findBy($criteria, $orderBy, $limit, $offset);
	}

	/**
	 * @param $criteria
	 * @param array|null $orderBy
	 * @param $limit
	 * @param $offset
	 * @return PostCollection
	 */
	public function getPosts($criteria=[], array $orderBy = null, $limit = null, $offset = null){

		if( is_string($criteria) )
			$criteria = ['post_type'=>$criteria];

		$postRepository = new PostRepository();

		return $postRepository->findBy($criteria, $orderBy, $limit, $offset);
	}

	/**
	 * @param $criteria
	 * @param array|null $orderBy
	 * @param $limit
	 * @param $offset
	 * @return TermCollection
	 */
	public function getTerms($criteria=[], array $orderBy = null, $limit = null, $offset = null){

		if( is_string($criteria) )
			$criteria = ['taxonomy'=>$criteria];

		$termRepository = new TermRepository();

		return $termRepository->findBy($criteria, $orderBy, $limit, $offset);
	}

	/**
	 * @return OptionsHelper
	 */
	public function getOptions(): OptionsHelper
	{
		return $this->options;
	}

	/**
	 * @param $key
	 * @return array|object|string|null
	 */
	public function getOption($key){

		return $this->options->getValue($key);
	}

	/**
	 * @param $key
	 * @param $value
	 * @return array|object|string|null
	 */
	public function setOption($key, $value){

		return $this->options->setValue($key, $value);
	}

	/**
	 * @return string
	 */
	public function getLanguage(): string
	{
		if( is_null($this->language) )
			$this->language = get_bloginfo('language');

		return $this->language;
	}

	/**
	 * @return string
	 */
	public function getCharset(): string
	{
		if( is_null($this->charset) )
			$this->charset = get_bloginfo('charset');

		return $this->charset;
	}

	/**
	 * @return string
	 */
	public function getDescription(): string
	{
		if( is_null($this->description) )
			$this->description = get_bloginfo('description');

		return $this->description;
	}

    /**
     * @param string $post_type
     * @return false|string
     */
    public function getArchiveLink(string $post_type){

        $post_type_obj = get_post_type_object( $post_type );

        if( $post_type_obj->has_archive )
            return get_post_type_archive_link($post_type);

        if( function_exists('get_page_by_state') ){

            if( $post = get_page_by_state('archive_'.$post_type) )
                return get_permalink($post);
            elseif( $post = get_page_by_state($post_type.'_archive') )
                return get_permalink($post);
        }

        return false;
    }

    /**
     * @return string
     */
    public function getArchiveTitle($post_type, $prefix=''){

        $title = '';

        $post_type_obj = get_post_type_object( $post_type );

        if( $post_type_obj->has_archive ){

            $title = apply_filters( 'post_type_archive_title', $post_type_obj->labels->name, $post_type );
        }
        elseif( function_exists('get_page_by_state') ){

            if( $post = get_page_by_state('archive_'.$post_type) )
                $title = get_the_title($post);
            elseif( $post = get_page_by_state($post_type.'_archive') )
                $title = get_the_title($post);
        }

        return $prefix . $title;
    }

    /**
     * @param string $redirect
     * @param bool $force_reauth
     * @return string
     */
    public function getLoginLink($redirect = '', $force_reauth = false){

        return wp_login_url($redirect, $force_reauth);
    }

	/**
	 * @return string
	 */
	public function getLocale(): string
	{
		if( is_null($this->locale) ){

			$language = explode('-', $this->getLanguage());
			$this->locale = count($language) ? $language[0] : 'en';
		}

		return $this->locale;
	}

    /**
     * return lang attribute
     */
    public function getLanguageAttributes(): string
    {
        return 'lang="'.$this->getLocale().'"';
    }

	/**
	 * @return string
	 */
	public function isSingle(): string
	{
		if( is_null($this->is_single) ){

			$queried_object = $this->getQueriedObject();
			$this->is_single = $queried_object->post_type??false;
		}

		return $this->is_single;
	}

	/**
	 * @return string
	 */
	public function isTax(): string
	{
		if( is_null($this->is_tax) ){

			$queried_object = $this->getQueriedObject();
			$this->is_tax = $queried_object->taxonomy??false;
		}

		return $this->is_tax;
	}

	/**
	 * @return string
	 */
	public function isArchive(): string
	{
		if( is_null($this->is_archive) ){

			$queried_object = $this->getQueriedObject();

            $type = is_object($queried_object) ? get_class($queried_object) : false;

            if( $type == 'WP_Post_Type')
                $this->is_archive = $queried_object->name;
            elseif( $type == 'WP_Term')
                $this->is_archive = $queried_object->taxonomy;
            else
                $this->is_archive = false;
		}

		return $this->is_archive;
	}

	/**
	 * @return bool
	 */
	public function isFrontPage(): bool
	{
		if( is_null($this->is_front_page) )
			$this->is_front_page = is_front_page();

		return $this->is_front_page;
	}

	/**
	 * @return bool
	 */
	public function isCustomizePreview(): bool
	{
		if( is_null($this->is_customize_preview) )
			$this->is_customize_preview = is_customize_preview();

		return $this->is_customize_preview;
	}

	/**
	 * @return bool
	 */
	public function isAdmin(): bool
	{
		if( is_null($this->is_admin) )
			$this->is_admin = current_user_can('manage_options');

		return $this->is_admin;
	}

	/**
	 * @return int
	 */
	public function getPaged(): int
	{
		if( is_null($this->paged) )
			$this->paged = max(1, get_query_var('paged', 0));

		return $this->paged;
	}

	/**
	 * @return bool
	 */
	public function getMaintenanceMode(): bool
	{
		if( is_null($this->maintenance_mode) )
			$this->maintenance_mode = function_exists('wp_maintenance_mode') && wp_maintenance_mode();

		return $this->maintenance_mode;
	}

	/**
	 * @return bool
	 */
	public function getDebug(): bool
	{
		if( is_null($this->debug) )
			$this->debug = WP_DEBUG;

		return $this->debug;
	}

	/**
	 * @return bool
	 */
	public function getEnvironment(): bool
	{
		if( is_null($this->environment) )
			$this->environment = WP_ENV;

		return $this->environment;
	}

	/**
	 * @return string
	 */
	public function getVersion(): ?string
	{
        if(is_null($this->version) && file_exists(BASE_URI.'/composer.json')){

            $composer = json_decode(file_get_contents(BASE_URI.'/composer.json'), true);
            $this->version = $composer['version']??'1.0.0';
        }

        return $this->version;
    }

	/**
	 * @return array
	 */
	public function getBreadcrumb()
    {
        if(is_null($this->breadcrumb) ){

            $breadcrumbServcie = new BreadcrumbService();
            $this->breadcrumb = $breadcrumbServcie->build();
        }

        return $this->breadcrumb;
    }

	/**
	 * @return array
	 */
	public function getPagination(): array
    {
        if(is_null($this->pagination) ){

            $paginationService = new PaginationService();
            $this->pagination = $paginationService->build();
        }

        return $this->pagination;
    }

	/**
	 * @return User|false
	 */
	public function getUser(){

        if( is_null($this->user) ){

            if( $user_id = get_current_user_id() )
                $this->user = Factory::create($user_id, 'user');
            else
                $this->user = false;
        }

        return $this->user;
    }

	/**
	 * @param string|null $location
	 * @return ClassHelper|Menu
	 */
	public function getMenu(?string $location=null){

		if( is_null($this->menu) )
			$this->menu = new ClassHelper(Menu::class);

        if( !$location )
	        return $this->menu;

        return $this->menu->__call($location);
    }

    /**
     * @deprecated
     *
     * @return string
     */
    public function getWpTitle(){

        return $this->getTitle();
    }

	/**
	 * @return string
	 */
	public function getTitle(): string
    {
        if(is_null($this->title) ){

            $wp_title = trim(@wp_title(' ', false));
            $this->title = html_entity_decode(empty($wp_title) ? get_the_title( get_option('page_on_front') ) : $wp_title);
        }

        return $this->title;
    }

	/**
	 * @return string
	 */
	public function getBodyClass(): string
    {
        if(is_null($this->body_class) ){

            $body_class = !is_404() ? implode(' ', @get_body_class()) : '';
            $this->body_class = $this->language . ' ' . preg_replace('/^-template-default/', 'template-default', $body_class);
        }

        return $this->body_class;
    }

	/**
	 * @deprecated
	 */
	public function getHomeUrl($path = '', $scheme = null): string
    {
        return $this->getHomeLink($path, $scheme);
    }

	/**
	 * @param string $path
	 * @param null $scheme
	 * @return string
	 */
	public function getHomeLink($path = '', $scheme = null): string
    {
	    if( !empty($path) )
		    return home_url($path, $scheme);

	    if( is_null($this->home_url) )
            $this->home_url = home_url();

        return $this->home_url;
    }

	/**
	 * @deprecated
	 */
	public function getSearchUrl($query=''): string
    {
        return $this->getSearchLink($query);
    }

	/**
	 * @param string $query
	 * @return string
	 */
	public function getSearchLink($query=''): string
    {
		if( !empty($query) )
			return get_search_link($query);

        if(is_null($this->search_url) ){

	        $search_url = get_search_link();
	        $search_query = get_search_query( false );

			if( !empty($search_query) )
				$this->search_url = str_replace('/'.$search_query, '', $search_url);
			else
				$this->search_url = $search_url;
        }

        return $this->search_url;
    }

	/**
	 * @deprecated
	 */
	public function getPrivacyPolicyUrl(): string
    {
        return $this->getPrivacyPolicyLink();
    }

	/**
	 * @return string
	 */
	public function getPrivacyPolicyLink(): string
    {
        if(is_null($this->privacy_policy_url) )
            $this->privacy_policy_url = get_privacy_policy_url();

        return $this->privacy_policy_url;
    }

	/**
	 * @return string
	 */
	public function getPrivacyPolicyTitle(): string
    {
        if(is_null($this->privacy_policy_title) ){

	        $policy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );
	        $this->privacy_policy_title = ( $policy_page_id ) ? get_the_title( $policy_page_id ) : '';
        }

        return $this->privacy_policy_title;
    }

	/**
	 * @deprecated
	 */
	public function getNetworkHomeUrl($path = '', $scheme = null): string
	{
        return $this->getNetworkHomeLink($path, $scheme);
    }

	/**
	 * @param string $path
	 * @param null $scheme
	 * @return string
	 */
	public function getNetworkHomeLink($path = '', $scheme = null): string
	{
		if( !empty($path) )
			return network_home_url($path, $scheme);

        if(is_null($this->network_home_url) )
            $this->network_home_url = trim(network_home_url(), '/');

        return $this->network_home_url;
    }

	/**
	 * @param string $name
	 * @deprecated use getInfo
	 * @return mixed|string|null
	 */
	public function getBloginfo(string $name){

        return $this->getInfo($name);
    }

	/**
	 * @param string|null $name
	 * @return mixed|string|null
	 */
	public function getInfo(?string $name=null){

		if(is_null($this->info) )
			$this->info = new FunctionHelper('get_bloginfo');

		if( !$name )
			return $this->info;

		return $this->info->__call($name);
    }

	/**
	 * @return int
	 */
	public function getPostsPerPage(): int
    {
        if(is_null($this->posts_per_page) )
            $this->posts_per_page = intval(get_option( 'posts_per_page' ));

        return $this->posts_per_page;
    }

	/**
	 * @return string
	 */
	public function getDomain(): string
	{
        if(is_null($this->domain) )
            $this->domain = strtok(preg_replace('/https?:\/\//', '', home_url('')),':');

        return $this->domain;
    }

    /**
     * Get multisite multilingual data
     * @return array
     */
	public function getLanguages(): array
    {
	    if( !is_null($this->languages) )
			return $this->languages;

	    $this->languages = [];

	    if( !is_multisite() )
			return $this->languages;

	    if( defined('ICL_LANGUAGE_CODE') )
	    {
		    $this->languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=desc' );
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

	            $this->languages[] = [
                    'id' => $site->blog_id,
                    'active' => $current_blog_id==$site->blog_id,
                    'name' => format_code_lang($lang),
                    'home_url'      => get_home_url($site->blog_id, '/'),
                    'language_code' => $lang,
                    'url'           => $alternate
                ];
            }
        }

        return $this->languages;
    }

    /**
     * @param MslsOptions $mslsOptions
     * @param \WP_Site $site
     * @param string $locale
     * @return false|string
     */
    protected function getAlternativeLink(MslsOptions $mslsOptions, \WP_Site $site, string $locale)
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

    /**
     * Todo: deprecate
     * @param Environment $twig
     * @return void
     */
    public function setGlobals($twig){

        $data = $this->__toArray();

        foreach ($data as $key=>$value)
            $twig->addGlobal($key, $value);
    }
}
