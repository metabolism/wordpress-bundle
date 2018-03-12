<?php

namespace Metabolism\WordpressBundle\Plugin {


/**
 * Class Metabolism\WordpressBundle Framework
 */
	class CachePlugin
	{
		/**
		 * Add maintenance button and checkbox
		 */
		public function clearCache()
		{
			if( class_exists('\App\Entity\HTTPCache') )
			{
				$cache = new \App\Entity\HTTPCache();
				$cache->clear();
			}
		}


		/**
		 * Add maintenance button and checkbox
		 */
		public function deleteCache($ID)
		{
			if( class_exists('\App\Entity\HTTPCache') )
			{
				$permalink = get_permalink( $ID );

				$cache = new \App\Entity\HTTPCache();
				$cache->delete($permalink);
			}
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
					'title' => __('Clear cache'),
					'href'  => '?clear_cache'
				];

				$wp_admin_bar->add_node( $args );

			}, 999 );
		}


		public function __construct($config)
		{
			if( isset($_GET['clear_cache']) )
				$this->clearCache();

			add_action( 'init', [$this, 'addClearCacheButton']);
			add_action( 'save_post', [$this, 'deleteCache'] );
		}
	}
}
