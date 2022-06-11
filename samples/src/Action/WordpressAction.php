<?php

namespace App\Action;

use Metabolism\WordpressBundle\Action\WordpressAction as WordpressSuperController;

class WordpressAction extends WordpressSuperController
{
	/**
	 * Execute code when the admin or front is loaded
	 * Equivalent to add_action('init', function(){ })
	 */
	public function init()
	{
		//add_action
	}
}
