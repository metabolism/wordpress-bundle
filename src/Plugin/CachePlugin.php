<?php

namespace Metabolism\WordpressBundle\Plugin;

use Dflydev\DotAccessData\Data;
use Metabolism\WordpressBundle\Helper\CacheHelper;
use Metabolism\WordpressBundle\Traits\SingletonTrait;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class CachePlugin
{
	use SingletonTrait;

	private $noticeMessage,  $errorMessage, $cacheHelper;


	/**
	 * Add maintenance button and checkbox
	 * @param bool $pid
	 * @return bool|void
	 */
	public function purgeCache($pid=false)
	{
		if( $pid ){

			$post = get_post($pid);

			if( $post && $post->post_status === 'publish' ){

				$home_url = get_home_url(null);
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
        $results = $this->cacheHelper->purgeUrl($url);

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
		$response = $this->cacheHelper->clear();

		if ( !$response )
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
	 * @param Data $config
	 */
	public function __construct($config)
	{
		$env = isset($_SERVER['APP_ENV'])?$_SERVER['APP_ENV']:'dev';
		$debug = (bool) ( isset($_SERVER['APP_DEBUG'])?$_SERVER['APP_DEBUG']:('prod' !== $env));

		$this->cacheHelper = new CacheHelper();

		if( isset($_GET['cache']) && $_GET['cache'] == 'purge' )
			$this->purge();

		if( isset($_GET['cache']) && $_GET['cache'] == 'clear' )
			$this->clear();

		if( !$debug ) {

			add_action( 'init', [$this, 'addClearCacheButton']);

			foreach (['save_post', 'deleted_post', 'trashed_post', 'edit_post', 'delete_attachment'] as $action)
				add_action( $action, [$this, 'purgeCache']);
		}

		add_action( 'reset_cache', [$this, 'reset']);
	}
}
