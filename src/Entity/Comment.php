<?php

namespace Metabolism\WordpressBundle\Entity;


/**
 * Class Comment
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Comment extends Entity
{
	public $entity = 'comment';

	public $post_ID;
	public $author;
	public $author_email;
	public $author_url;
	public $author_IP;
	public $date;
	public $date_gmt;
	public $content;
	public $karma;
	public $approved;
	public $parent;
	public $user_id;
	public $replies=[];

    public function __toString()
    {
        return $this->content;
    }

	/**
	 * Post constructor
	 * @param null $id
	 */
	public function __construct($id = null) {

		if( $comment = $this->get($id) )
		{
            $this->ID = intval($comment->comment_ID);
            //todo: bind
		}
    }


	/**
	 * @param $pid
	 * @return array|\WP_Comment|null
	 */
	protected function get($pid ) {

		return get_comment($pid);
	}


	/**
	 * @param array $data {
	 *     Comment data.
	 *
	 *     @type string|int $comment_post_ID             The ID of the post that relates to the comment.
	 *     @type string     $author                      The name of the comment author.
	 *     @type string     $email                       The comment author email address.
	 *     @type string     $url                         The comment author URL.
	 *     @type string     $comment                     The content of the comment.
	 *     @type string|int $comment_parent              The ID of this comment's parent, if any. Default 0.
	 *     @type string     $_wp_unfiltered_html_comment The nonce value for allowing unfiltered HTML.
	 * }
	 * @return bool|\WP_Error
	 */
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
