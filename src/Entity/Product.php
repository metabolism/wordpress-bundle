<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Metabolism\WordpressBundle\Entity;


/**
 * Class Post
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Product extends Post
{
	public $wc;

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
