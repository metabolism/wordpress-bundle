<?php

namespace App\Action;

use Metabolism\WordpressBundle\Action\WordpressAction as WordpressSuperAction;

class WordpressAction extends WordpressSuperAction
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
