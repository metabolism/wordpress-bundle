<?php

// bad design but required by to make wp style function
namespace Metabolism\WordpressLoader\Plugin {


/**
 * Class Metabolism\WordpressLoader Framework
 */
	class MaintenancePlugin {


		/**
		 * Add maintenance button and checkbox
		 */
		public function addMaintenanceMode()
		{
			if( is_admin() )
			{
				add_action( 'admin_init', function(){

					add_settings_field('maintenance_field', __('Maintenance Mode'), function(){

						echo '<input type="checkbox" id="maintenance_field" name="maintenance_field" value="1" ' . checked( 1, get_option('maintenance_field'), false ) . ' />'.__('Activate maintenance mode');

					}, 'general');

					register_setting('general', 'maintenance_field');
				});
			}

			add_action( 'admin_bar_menu', function( $wp_admin_bar )
			{
				if( !is_admin() && is_post_type_archive() )
				{
					$object = get_queried_object();

					$args = [
						'id'    => 'edit',
						'title' => __('Edit Posts'),
						'href'  => get_admin_url( null, '/edit.php?post_type='.$object->name ),
						'meta'   => ['class' => 'ab-item']
					];

					$wp_admin_bar->add_node( $args );
				}

				$args = [
					'id'    => 'maintenance',
					'title' => __('Maintenance mode').' : '.( get_option( 'maintenance_field', false) ? __('On') : __('Off')),
					'href'  => get_admin_url( null, '/options-general.php#maintenance_field' )
				];

				$wp_admin_bar->add_node( $args );

			}, 999 );
		}


		public function __construct($config)
		{
			add_action( 'init', function()
			{
				$this->addMaintenanceMode();
			});
		}
	}
}

namespace {

	function wp_maintenance_mode($strict = false)
	{
		if ($strict)
			return get_option('maintenance_field', false);
		else
			return !current_user_can('editor') && !current_user_can('administrator') && get_option('maintenance_field', false);
	}
}
