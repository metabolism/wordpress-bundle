<?php

namespace Metabolism\WordpressBundle\Entity;


use Metabolism\WordpressBundle\Factory\Factory;
use Metabolism\WordpressBundle\Factory\PostFactory;

/**
 * Class Comment
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Comment extends Entity
{
	public $entity = 'comment';

	protected $post;
	protected $author;
	protected $agent;
	protected $author_email;
	protected $author_url;
	protected $author_IP;
	protected $content;
	protected $karma;
	protected $approved;
	protected $parent;
    protected $user;
	protected $replies;
	protected $date;
	protected $date_gmt;

	/** @var \WP_Comment */
	protected $comment;

    public function __toString()
    {
        return $this->content??'Invalid comment';
    }

	/**
	 * Post constructor
	 * @param null $id
	 */
	public function __construct($id = null) {

		if( $comment = $this->get($id) )
		{
            $this->ID = intval($comment->comment_ID);
			$this->approved = $comment->comment_approved;
			$this->agent = $comment->comment_agent;
			$this->author = $comment->comment_author;
			$this->author_email = $comment->comment_author_email;
			$this->author_IP = $comment->comment_author_IP;
			$this->author_url = $comment->comment_author_url;
			$this->content = $comment->comment_content;
			$this->karma = $comment->comment_karma;
		}
    }


	/**
	 * @param $pid
	 * @return array|\WP_Comment|false
	 */
	protected function get($pid ) {

		if( $comment = get_comment($pid) ){

			if( is_wp_error($comment) || !$comment )
				return false;

			$this->comment = $comment;
		}

		return $comment;
	}

	/**
	 * @return Post
	 */
	public function getPost()
	{
		if( is_null($this->post) )
			$this->post = PostFactory::create($this->comment->comment_post_ID);

		return $this->post;
	}

	/**
	 * @return string
	 */
	public function getAuthor(): string
	{
		return $this->author;
	}

	/**
	 * @return string
	 */
	public function getAgent(): string
	{
		return $this->agent;
	}

	/**
	 * @return string
	 */
	public function getAuthorEmail(): string
	{
		return $this->author_email;
	}

	/**
	 * @return string
	 */
	public function getAuthorUrl(): string
	{
		return $this->author_url;
	}

	/**
	 * @return string
	 */
	public function getAuthorIP(): string
	{
		return $this->author_IP;
	}

	/**
	 * @return string
	 */
	public function getContent(): string
	{
		return $this->content;
	}

	/**
	 * @return int|string
	 */
	public function getKarma()
	{
		return $this->karma;
	}

	/**
	 * @return string
	 */
	public function getApproved(): string
	{
		return $this->approved;
	}

	/**
	 * @return User
	 */
	protected function getUser() {

        if( is_null($this->user) )
            $this->user = Factory::create($this->comment->user_id, 'user');

        return $this->user;
	}

	public function getDate($format=true){

		if( is_null($this->date) && $format )
			$this->date = $this->formatDate($this->comment->comment_date);

		return $format ? $this->date : $this->comment->comment_date;
	}

	public function getParent(){

		if( is_null($this->parent) )
			$this->parent = Factory::create($this->comment->comment_parent, 'comment');

		return $this->parent;
	}

	public function getDateGmt($format=true){

		if( is_null($this->date_gmt) && $format )
			$this->date_gmt = $this->formatDate($this->comment->comment_date_gmt);

		return $format ? $this->date_gmt : $this->comment->comment_date_gmt;
	}

	public function getReplies($args=[]){

		if( is_null($this->replies) ){

			$default_args = [
				'status' => 'approve',
				'number' => '3'
			];

			$args = array_merge($default_args, $args);

			$args['parent'] = $this->ID;
			$args['type'] = 'comment';
			$args['fields'] = 'ids';

			$comments_id = get_comments($args);

			$replies = [];

			foreach ($comments_id as $comment_id)
			{
				/** @var Comment $comment */
				$comment = Factory::create($comment_id, 'comment');
				$replies[$comment_id] = $comment;
			}

			$this->replies = $replies;
		}

		return $this->replies;
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
	public static function handleSubmission($data){

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


    /**
     * @deprecated
     */
    public static function post($data){

		return self::handleSubmission($data);
	}
}
