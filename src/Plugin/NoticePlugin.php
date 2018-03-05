<?php

namespace Metabolism\WordpressBundle\Plugin;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class NoticePlugin {

	protected $config;

	/**
	 * Check if ACF and Timber are enabled
	 */
	public function pluginsLoaded()
	{
		$notices = [];

		if ( !class_exists( 'acf' ) )
			$notices[] = '<div class="error"><p>Advanced Custom Fields not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#acf' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';

		if( !empty($notices) )
		{
			add_action( 'admin_notices', function() use($notices)
			{
				echo implode('<br/>', $notices );
			});
		}
	}


	/**
	 * Check symlinks and forders
	 */
	public function adminNotices(){

		if( !$this->config->get('debug.message') )
			return;

		$notices = [];

		//check folder wright
		foreach (['vendor/wordpress/languages', 'web/uploads', 'vendor/wordpress/upgrade', 'config/acf-json'] as $folder ){

			$path = BASE_URI.'/'.$folder;

			if( !file_exists($path) )
				$notices [] = $folder.' folder doesn\'t exist';
			elseif( !is_writable($path) )
				$notices [] = $folder.' folder is not writable';
		}

		if( !empty($notices) )
			echo '<div class="error"><p>'.implode('<br/>', $notices ).'</p></div>';
	}


	public function __construct($config)
	{
		$this->config = $config;

		if( is_admin() )
		{
			add_action( 'plugins_loaded', [$this, 'pluginsLoaded']);
			add_action( 'admin_notices', [$this, 'adminNotices']);
		}
	}
}
