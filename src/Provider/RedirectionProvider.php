<?php

namespace Metabolism\WordpressBundle\Provider;

use Dflydev\DotAccessData\Data;

/**
 * Class WPSmartCropProvider
 *
 * @package Metabolism\WordpressBundle\Provider
 */
class RedirectionProvider
{
	/**
	 * Construct
	 * @param Data $config
	 */
	public function __construct($config)
	{
		$role = $config->get('plugins.redirection.redirection_role');

		if( $role ){

			add_filter('redirection_role', function($cap) use($role) {
				return $role;
			});
		}
	}
}