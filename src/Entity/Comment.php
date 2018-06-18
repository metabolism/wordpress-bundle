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
class Comment extends Entity
{
	/**
	 * Post constructor.
	 *
	 * @param null $id
	 */
	public function __construct($id = null) {

		if( $comment = $this->get($id) )
		{
			$this->import($comment, false, 'comment_');
		}
	}


	private function get( $pid ) {

		$comment = get_comment($pid);

		return $comment;
	}
}
