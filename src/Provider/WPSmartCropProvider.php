<?php

namespace Metabolism\WordpressBundle\Provider;

/**
 * Class WPSmartCropProvider
 *
 * @package Metabolism\WordpressBundle\Provider
 */
class WPSmartCropProvider
{
	/**
	 * Construct
	 */
	public function __construct()
	{
		if( !is_admin() ) {

			add_action('init', function() {

				if( class_exists('WP_Smart_Crop') ){

					$instance = \WP_Smart_Crop::Instance();
					remove_action('wp_enqueue_scripts', array($instance, 'wp_enqueue_scripts'));
				}
			});
		}
	}
}