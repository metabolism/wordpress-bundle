<?php

namespace App\Controller;

use Metabolism\WordpressBundle\Controller\WordpressController as WordpressSuperController;

class WordpressController extends WordpressSuperController
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
