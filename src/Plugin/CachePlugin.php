<?php

namespace Metabolism\WordpressBundle\Plugin {

	use Dflydev\DotAccessData\Data;


/**
 * Class Metabolism\WordpressBundle Framework
 */
	class CachePlugin
	{

		private $noticeMessage,  $errorMessage;

		/**
		 * Add maintenance button and checkbox
		 */
		public function purgeCache($postId=false)
		{
			if( $postId )
				$url = get_permalink($postId);
			else
				$url = get_home_url(null, '*');

			$this->purgeUrl($url);
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
		 */
		private function purgeUrl($url)
		{
			$args = ['method' => 'PURGE', 'headers' => ['Host' => $_SERVER['HTTP_HOST']], 'sslverify' => false];

			$url = str_replace($_SERVER['HTTP_HOST'], $_SERVER['SERVER_ADDR'], $url);

			$response = wp_remote_request($url, $args);

			if ( is_wp_error($response) ) {
				$this->errorMessage = $url.' : '.$response->get_error_code().' '.$response->get_error_message();
			} elseif ( is_array($response) and isset($response['response']) ) {
				$this->noticeMessage = $url.' : '.$response['response']['code'].' '.$response['response']['message'];
			}


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
		 * Clear cache folder
		 */
		private function clearCache(){
			if( !empty(BASE_URI) )
				$this->rrmdir(BASE_URI.'/var/cache');

			wp_redirect( get_admin_url(null, 'options-general.php' ));
		}


		/**
		 * Recursive rmdir
		 * @param string $dir
		 */
		private function rrmdir($dir) {
			
			if (is_dir($dir)) {
				$objects = scandir($dir);
				foreach ($objects as $object) {
					if ($object != "." && $object != "..") {
						if (is_dir($dir."/".$object))
							$this->rrmdir($dir."/".$object);
						else
							unlink($dir."/".$object);
					}
				}
				rmdir($dir);
			}
		}


		/**
		 * add admin parameters
		 */
		public function adminInit(){

			if( isset($_GET['forceclearcache']) )
				$this->clearCache();

			add_settings_field('cache', __('Cache'), function(){

				echo '<a class="button button-primary" href="'.get_admin_url().'?forceclearcache">'.__('Force clear').'</a>';

			}, 'general');
		}


		/**
		 * CachePlugin constructor.
		 * @param Data $config
		 */
		public function __construct($config)
		{
			$env = $_SERVER['APP_ENV'] ?? 'dev';
			$debug = (bool) ($_SERVER['APP_DEBUG'] ?? ('prod' !== $env));

			if( isset($_GET['purge_cache']) )
				$this->purgeCache();

			add_action( 'admin_init', [$this, 'adminInit']);

			if( !$debug ) {

				add_action( 'init', [$this, 'addClearCacheButton']);

				foreach (['save_post', 'deleted_post', 'trashed_post', 'edit_post', 'delete_attachment'] as $action)
					add_action( $action, [$this, 'purgeCache']);
			}

			add_action( 'purge_cache', [$this, 'purgeCache']);
		}
	}
}
