<?php

namespace Metabolism\WordpressBundle\Plugin;

use Dflydev\DotAccessData\Data;
use Metabolism\WordpressBundle\Helper\Cache;
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
		if( !empty($this->noticeMessage) )
			echo '<div id="message" class="updated fade"><p><strong>' . __('Cache') . '</strong><br />' . $this->noticeMessage . '</p></div>';

		if( !empty($this->errorMessage) )
			echo '<div id="message" class="error fade"><p><strong>' . __('Cache') . '</strong><br />' . $this->errorMessage . '</p></div>';
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
		list($url, $response) = $this->cacheHelper->purgeUrl($url);
		
		if ( is_wp_error($response) )
			$this->errorMessage = $url.' : '.$response->get_error_code().' '.$response->get_error_message();
		elseif ( is_array($response) and isset($response['response']) )
			$this->noticeMessage = $url.' : '.$response['response']['code'].' '.$response['response']['message'];

		add_action('admin_notices', [$this, 'message'], 999);
	}


	/**
	 * Clear cache
	 */
	private function clear()
	{
		$response = $this->cacheHelper->clear();

		if ( !$response )
			$this->errorMessage = 'Unable to clear cache';
		else
			$this->noticeMessage = 'Cleared';

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
		$env = $_SERVER['APP_ENV'] ?? 'dev';
		$debug = (bool) ($_SERVER['APP_DEBUG'] ?? ('prod' !== $env));

		$this->cacheHelper = new Cache();

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
