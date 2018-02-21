<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Metabolism\WordpressLoader\Model;

/**
 * Class Post
 * @see \Timber\Term
 *
 * @package Metabolism\WordpressLoader\Model
 */
class ImageModel extends \Timber\Image
{
	/**
	 * Post constructor.
	 *
	 * @param null $id
	 */
	public function __construct($id = null) {

		parent::__construct( $id );

		$this->clean();
	}


	/**
	 * Remove useless data
	 */
	protected function clean()
	{
		unset(
			$this->_wp_attachment_metadata, $this->sizes, $this->ImageClass, $this->PostClass, $this->TermClass,
			$this->post_content, $this->post_content_filtered, $this->post_excerpt, $this->ping_status, $this->to_ping, $this->post_name,
			$this->pinged, $this->_can_edit, $this->_dimensions, $this->_wp_attachment_image_alt, $this->slug
		);
	}
}
