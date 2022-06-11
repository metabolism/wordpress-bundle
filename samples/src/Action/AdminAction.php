<?php

namespace App\Action;

use Metabolism\WordpressBundle\Action\AdminAction as WordpressAdminController;

class AdminAction extends WordpressAdminController
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
