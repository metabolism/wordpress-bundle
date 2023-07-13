<?php

namespace App\Action;

use Metabolism\WordpressBundle\Action\FrontAction as WordpressFrontAction;

class FrontAction extends WordpressFrontAction
{
	/**
	 * Execute code when the front is loaded
	 * Equivalent to if( !is_admin() ) add_action('init', function(){ })
	 *
	 * If you want to execute code for both admin and front, create a WordpressController
     * please take a loot a samples/src/Action/WordpressAction.php in /vendor/metabolism/wordpress-bundle
	 */
	public function init()
	{
		//add_action
	}
}
