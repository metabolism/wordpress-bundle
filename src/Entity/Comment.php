<?php

namespace Metabolism\WordpressBundle\Entity;


/**
 * Class Comment
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


	protected function get( $pid ) {

		return get_comment($pid);
	}


	public static function post($data){

		$comment = wp_handle_comment_submission( wp_unslash( $data ) );

		if ( is_wp_error( $comment ) ) {

			$data = intval( $comment->get_error_data() );

			if ( ! empty( $data ) ) {
				return new \WP_Error('comment_error', $comment->get_error_message());
			} else {
				return new \WP_Error('comment_error', "An unknown error occurred");
			}
		}

		$user = wp_get_current_user();

		$cookies_consent = ( isset( $_POST['wp-comment-cookies-consent'] ) );

		do_action( 'set_comment_cookies', $comment, $user, $cookies_consent );

		return true;
	}
}
