<?php

namespace Metabolism\WordpressBundle\Plugin;

/**
 * Class
 */
class UrlPlugin {

    /**
     * Add edition folder to option url
     * @param $url
     * @return mixed
     */
    public function networkSiteURL($url)
    {
        if( WP_FOLDER && strpos($url, WP_FOLDER) === false )
        {
            $url = str_replace('/wp-login', WP_FOLDER.'/wp-login', $url);
	        return str_replace('/wp-admin', WP_FOLDER.'/wp-admin', $url);
        }
        else{

	        return $url;
        }
    }


    /**
     * Add edition folder to option url
     * @param $url
     * @return string
     */
    public function optionSiteURL($url)
    {
        if( WP_FOLDER )
            return strpos($url, WP_FOLDER) === false ? $url.WP_FOLDER : $url;
        else
            return $url;
    }


    /**
     * Add edition folder to option url
     * @param $url
     * @return mixed
     */
    public function homeURL($url)
    {
        if( WP_FOLDER )
            return str_replace(WP_FOLDER, '', $url);
        else
            return $url;
    }

    /**
     * Save post name when requesting for preview link
     * @param $id
     * @return mixed
     */
    public function getPreviewPermalink($id){

        $post = get_post($id);

        if( $post->post_name ){

            $post->post_status = 'publish';
            return get_permalink($post);
        }

        $filter = $post->filter ?? false;

        if( !function_exists('get_sample_permalink') && file_exists(ABSPATH . 'wp-admin/includes/post.php') )
            require_once(ABSPATH . 'wp-admin/includes/post.php');

        list($permalink, $post_name) = get_sample_permalink($post);
        $preview_permalink = str_replace( array( '%pagename%', '%postname%' ), $post_name, esc_html( urldecode( $permalink ) ) );

        $post->filter = $filter;

        if($post->post_name != $post_name)
            wp_update_post(['ID'=> $post->ID, 'post_name'=> $post_name]);

        return $preview_permalink;
    }


    /**
     * Symfony require real url so redirect preview url to real url
     * ex /?post_type=project&p=899&preview=true redirect to /project/post-title?preview=true
     */
    public function redirect(){

        $permalink = $query_args = false;

        if( isset($_GET['s']) ){

            $permalink = get_search_link(sanitize_text_field($_GET['s']));

            $query_args = $_GET;
            unset($query_args['s']);
        }
        else{

            $id = $_GET['p'] ?? $_GET['page_id'];
            $permalink = $this->getPreviewPermalink($id);

            $query_args['preview'] = 'true';
        }

        if( $permalink ){

            if( $query_args )
                $permalink = add_query_arg( $query_args, $permalink );

            wp_redirect($permalink);
            exit;
        }
    }


    /**
     * Symfony require real url so redirect preview url to real url
     * ex /?post_type=project&p=899&preview=true redirect to /project/post-title?preview=true
     * @param $permalink
     * @param $post
     * @return mixed
     */
    public function previewPostLink($permalink, $post){

        if( $post->post_name == '' ){

            $permalink = $this->getPreviewPermalink($post);

            $query_args['preview'] = 'true';
            $permalink = add_query_arg( $query_args, $permalink );
        }

        return $permalink;
    }


    /**
     * Set permalink structure
     */
    public function init()
    {
        if( !is_admin() && (isset($_GET['preview'], $_GET['p']) || isset($_GET['preview'], $_GET['page_id']) || isset($_GET['s']) ) )
            $this->redirect();

        global $wp_rewrite;

        $permalink_structure = '/%postname%';

        if( $wp_rewrite->permalink_structure != $permalink_structure ){

            $wp_rewrite->set_permalink_structure($permalink_structure);
            update_option( 'rewrite_rules', FALSE );
        }
    }

    /**
     * This function is to replace PHP's extremely buggy realpath().
     * @param string $path The original path, can be relative etc.
     * @param string $separator The separator.
     * @return string The resolved path, it might not exist.
     */
    private function realpath($path, $separator=DIRECTORY_SEPARATOR){

        $paths = explode($separator, $path);

        foreach ($paths as $key=>$path){
            if( $path == '..'){
                unset($paths[$key-1]);
                unset($paths[$key]);
            }
        }

        return implode($separator, $paths);
    }


    public function uploadDir( $arr )
    {
        $arr['path'] = $this->realpath($arr['path']);
        $arr['basedir'] = $this->realpath($arr['basedir']);

        $arr['url'] =  $this->realpath($arr['url'], '/');
        $arr['baseurl'] =  $this->realpath($arr['baseurl'], '/');

        return $arr;
    }


    /**
     * UrlPlugin constructor.
     */
    public function __construct(){

        add_filter('preview_post_link', [$this, 'previewPostLink'], 10, 2);
        add_filter('option_siteurl', [$this, 'optionSiteURL'] );
        add_filter('network_site_url', [$this, 'networkSiteURL'] );
        add_filter('home_url', [$this, 'homeURL'] );

        add_action('init', [$this, 'init'], 99);
        add_filter('upload_dir', [$this, 'uploadDir'], 10, 2);
    }
}
