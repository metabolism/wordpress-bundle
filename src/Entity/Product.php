<?php


namespace Metabolism\WordpressBundle\Entity;


/**
 * Class Product
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Product extends Post
{
	/**
	 * Post constructor.
	 *
	 * @param null $id
	 */
	public function __construct($id = null) {

		parent::__construct($id);

		if( function_exists('wc_get_product'))
			$this->wc = wc_get_product( $id );
	}
}
