<?php

namespace Metabolism\WordpressBundle\Plugin;

/**
 * Class 
 */
class CachePlugin
{
	private $noticeMessage,  $errorMessage, $debug;


	/**
	 * Add maintenance button and checkbox
	 * @param bool $pid
	 * @return void
	 */
	public function purgeCache($pid=false)
	{
		if( $pid ){

			$post = get_post($pid);
			$post_type_object = get_post_type_object($post->post_type);

			if( $post && $post->post_status === 'publish' && $post_type_object->publicly_queryable ){

				$home_url = get_home_url();
				$url = $home_url.get_permalink($pid);

				$this->purge($url);
			}
		}
	}


	public function message()
	{
        $html = '';

        if( !empty($this->noticeMessage) ){

            $html .= '<div id="message" class="updated fade"><p><strong>' . __('Cache') . '</strong><br />';

            foreach ( $this->noticeMessage as $message )
                $html .= $message.'<br/>';

            $html .= '</p></div>';
        }

        if( !empty($this->errorMessage) ){

            $html .= '<div id="message" class="error fade"><p><strong>' . __('Cache') . '</strong><br />';

            foreach ( $this->errorMessage as $message )
                $html .= $message.'<br/>';

            $html .= '</p></div>';
        }

        echo $html;
	}


	/**
	 * Reset cache
	 */
	public function reset()
	{
        $this->purge();
        $this->clear();
	}


	/**
	 * Purge cache
	 * @param bool $url
	 */
	private function purge($url=false)
	{
        if( $this->debug )
            return;

        $results = \WPS_Object_Cache::purgeUrl($url);

        foreach ($results as $result){

            if ( is_wp_error($result['request']) )
                $this->errorMessage[] = $result['url'].' : '.$result['request']->get_error_code().' '.$result['request']->get_error_message();
            elseif($result['request']['response']['code'] >= 300)
                $this->errorMessage[] = $result['url'].' : '.$result['request']['response']['code'].' '.$result['request']['response']['message'];
            else
                $this->noticeMessage[] = $result['url'].' : '.$result['request']['response']['code'].' '.$result['request']['response']['message'];
        }

        add_action('admin_notices', [$this, 'message'], 999);
	}


	/**
	 * Clear cache
	 */
	private function clear()
	{
		if ( !\WPS_Object_Cache::clear() )
			$this->errorMessage[] = 'Unable to clear cache';
		else
			$this->noticeMessage[] = 'Cleared';

		add_action('admin_notices', [$this, 'message'], 999);
	}


	/**
	 * Add maintenance button and checkbox
	 */
	public function addClearCacheButton()
	{
		add_action( 'admin_bar_menu', function( $wp_admin_bar )
		{
			$args = [
				'id'    => 'cache-purge',
				'title' => __('Purge cache'),
				'href'  => get_admin_url().'?cache=purge'
			];

			$wp_admin_bar->add_node( $args );

			if ( current_user_can('administrator') ){

				$args = [
					'id'    => 'cache-clear',
					'title' => __('Clear cache'),
					'href'  => get_admin_url().'?cache=clear'
				];

				$wp_admin_bar->add_node( $args );
			}

		}, 999 );
	}


	/**
	 * CachePlugin constructor.
	 */
	public function __construct()
	{
		$env = $_SERVER['APP_ENV'] ?? 'dev';
		$this->debug = (bool) ($_SERVER['APP_DEBUG'] ?? ('prod' !== $env));

		if( !$this->debug ) {

            if( isset($_GET['cache']) && $_GET['cache'] == 'purge' )
                $this->purge();

            if( isset($_GET['cache']) && $_GET['cache'] == 'clear' )
                $this->clear();

			add_action( 'init', function(){

				$this->addClearCacheButton();

				foreach (['save_post', 'deleted_post', 'trashed_post', 'edit_post'] as $action)
					add_action( $action, [$this, 'purgeCache']);
			});
		}

		add_action( 'reset_cache', [$this, 'reset']);
	}
}
