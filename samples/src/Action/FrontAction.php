<?php

namespace App\Action;

use Metabolism\WordpressBundle\Action\FrontAction as WordpressFrontController;

class FrontAction extends WordpressFrontController
{
	/**
	 * Execute code when the front is loaded
	 * Equivalent to if( !is_admin() ) add_action('init', function(){ })
	 *
	 * If you want to execute code for both admin and front, create a WordpressController
	 * please take a loot a samples/controller/WordpressController.php in /vendor/metabolism/wordpress-bundle
	 */
	public function init()
	{
		//add_action
	}
}
