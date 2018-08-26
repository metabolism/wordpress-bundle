<?php

namespace Metabolism\WordpressBundle\Entity;


/**
 * Class Menu
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class MenuItem extends Entity
{
	public function __construct( $data ) {
		
		if ( $data ){
			$this->import($data, false, 'post_');

			unset($this->date, $this->date_gmt, $this->modified, $this->modified_gmt, $this->name);
		}
	}
}
