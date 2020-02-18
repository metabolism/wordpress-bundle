<?php

// bad design but required by to make wp style function
namespace Metabolism\WordpressBundle\Plugin {

	use Dflydev\DotAccessData\Data;
	use Metabolism\WordpressBundle\Traits\SingletonTrait;


	/**
	 * Class Metabolism\WordpressBundle Framework
	 */
	class MaintenancePlugin {

		use SingletonTrait;

		private $config;

		/**
		 * Add maintenance button and checkbox
		 */
		public function addMaintenanceMode()
		{
			if( !current_user_can('editor') && !current_user_can('administrator') )
				return;
			
			if( is_admin() )
			{
				add_action( 'admin_init', function(){

					add_settings_field('maintenance_field', __('Maintenance Mode'), function(){

						echo '<input type="checkbox" id="maintenance_field" name="maintenance_field" value="1" ' . checked( 1, get_option('maintenance_field'), false ) . ' />'.__('Activate maintenance mode');

						if( isset($_REQUEST['settings-updated']) )
							do_action('reset_cache');

					}, 'general');

					register_setting('general', 'maintenance_field');
				});
			}

			add_action( 'admin_bar_menu', function( $wp_admin_bar )
			{
				$args = [
					'id'    => 'maintenance',
					'title' => __('Maintenance mode').' : '.( get_option( 'maintenance_field', false) ? __('On') : __('Off')),
					'href'  => get_admin_url( null, '/options-general.php#maintenance_field' )
				];

				$wp_admin_bar->add_node( $args );

			}, 999 );
		}

		/**
		 * MaintenancePlugin constructor.
		 * @param Data $config
		 */
		public function __construct($config)
		{
			$this->config = $config;

			if( $this->config->get('maintenance', true) )
				add_action( 'init', [$this, 'addMaintenanceMode']);
		}
	}
}

namespace {

	/**
	 * @param bool $strict
	 * @return bool
	 */
	function wp_maintenance_mode($strict = false)
	{
		if ($strict)
			return get_option('maintenance_field', false);
		else
			return !current_user_can('editor') && !current_user_can('administrator') && get_option('maintenance_field', false);
	}
}
