<?php

namespace Metabolism\WordpressBundle\Plugin {

	use Dflydev\DotAccessData\Data;
	use Metabolism\WordpressBundle\Helper\Cache;


/**
 * Class Metabolism\WordpressBundle Framework
 */
	class CachePlugin
	{

		private $noticeMessage,  $errorMessage, $cacheHelper;


		/**
		 * Add maintenance button and checkbox
		 */
		public function purgeCache($pid=false)
		{
			if( $pid ){

				$post = get_post($pid);

				if( $post && $post->post_status === 'publish' ){

					$home_url = get_home_url(null);
					$url = $home_url.get_permalink($pid);

					return $this->purge($url);
				}
			}

			return false;
		}


		public function purgeMessage()
		{
			if( !empty($this->noticeMessage) )
				echo '<div id="message" class="updated fade"><p><strong>' . __('Cache purge') . '</strong><br />' . $this->noticeMessage . '</p></div>';

			if( !empty($this->errorMessage) )
				echo '<div id="message" class="error fade"><p><strong>' . __('Cache purge') . '</strong><br />' . $this->errorMessage . '</p></div>';
		}


		/**
		 * Add maintenance button and checkbox
		 * @param bool $url
		 */
		private function purge($url=false)
		{
			$response = $this->cacheHelper->purgeUrl($url);

			if ( is_wp_error($response) )
				$this->errorMessage = $url.' : '.$response->get_error_code().' '.$response->get_error_message();
			elseif ( is_array($response) and isset($response['response']) )
				$this->noticeMessage = $url.' : '.$response['response']['code'].' '.$response['response']['message'];

			add_action('admin_notices', [$this, 'purgeMessage'], 999);
		}


		/**
		 * Add maintenance button and checkbox
		 */
		public function addClearCacheButton()
		{
			add_action( 'admin_bar_menu', function( $wp_admin_bar )
			{
				$args = [
					'id'    => 'cache',
					'title' => __('Purge cache'),
					'href'  => get_admin_url().'?purge_cache'
				];

				$wp_admin_bar->add_node( $args );

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

			if( isset($_GET['purge_cache']) )
				$this->purge();

			if( !$debug ) {

				add_action( 'init', [$this, 'addClearCacheButton']);

				foreach (['save_post', 'deleted_post', 'trashed_post', 'edit_post', 'delete_attachment'] as $action)
					add_action( $action, [$this, 'purgeCache']);
			}

			add_action( 'purge_cache', [$this, 'purgeCache']);
		}
	}
}
