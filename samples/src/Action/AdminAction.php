<?php

namespace App\Action;

use Metabolism\WordpressBundle\Action\AdminAction as WordpressAdminAction;

class AdminAction extends WordpressAdminAction
{
	/**
	 * Execute code when the admin is loaded
	 * Equivalent to if( is_admin() ) add_action('admin_init', function(){ })
	 *
	 * If you want to execute code for both admin and front, create a WordpressAction
	 * please take a loot a samples/src/Action/WordpressAction.php in /vendor/metabolism/wordpress-bundle
	 */
	public function init()
	{
		//add_action
	}
}
