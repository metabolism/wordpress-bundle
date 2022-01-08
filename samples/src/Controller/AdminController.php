<?php

namespace App\Controller;

use Metabolism\WordpressBundle\Controller\AdminController as WordpressAdminController;

class AdminController extends WordpressAdminController
{
	/**
	 * Execute code when the admin is loaded
	 * Equivalent to if( is_admin() ) add_action('admin_init', function(){ })
	 *
	 * If you want to execute code for both admin and front, create a WordpressController
	 * please take a loot a samples/controller/WordpressController.php in /vendor/metabolism/wordpress-bundle
	 */
	public function init()
	{
		//add_action
	}
}
