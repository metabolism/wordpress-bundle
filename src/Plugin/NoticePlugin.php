<?php

namespace Metabolism\WordpressLoader\Plugin;


/**
 * Class Metabolism\WordpressLoader Framework
 */
class NoticePlugin {

	protected $config;

	/**
	 * Check if ACF and Timber are enabled
	 */
	public function pluginsLoaded()
	{
		$notices = [];

		if ( !class_exists( 'Timber' ) )
			$notices [] = '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';

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
		foreach (['src/WordpressBundle/languages', 'src/WordpressBundle/uploads', 'src/WordpressBundle/upgrade'] as $folder ){

			$path = BASE_URI.'/'.$folder;

			if( !file_exists($path) or !is_writable($path) )
				$notices [] = $folder.' folder doesn\'t exist or is not writable';
		}

		if( !empty($notices) )
			echo '<div class="error"><p>'.implode('<br/>', $notices ).'</p></div>';


		$notices = [];

		//check symlink
		foreach (['web/uploads', 'web/plugins', 'web/ajax.php', 'web/static'] as $file ){

			$path = BASE_URI.'/'.$file;

			if( !is_link($path) )
				$notices [] = $file.' is not a valid symlink';
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
