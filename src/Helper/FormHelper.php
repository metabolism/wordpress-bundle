<?php

namespace Metabolism\WordpressBundle\Helper;

use Metabolism\WordpressBundle\Plugin\MediaPlugin as Media;


/**
 * Class Metabolism\WordpressBundle Framework
 */
class Form {

	/**
	 * Get request parameter
	 */
	public static function getField( $data, $key, $limit_lengh=500 )
	{
		if( !$data )
			$data = $_REQUEST;

		if( isset($_FILES[$key]))
		{
			$upload = Media::upload($key, ['image/jpeg', 'image/gif', 'image/png', 'application/pdf', 'application/zip']);

			if( is_wp_error($upload) )
				return false;

			return $upload['filename'];
		}
		elseif ( !isset( $data[ $key ] ) )
		{
			return false;
		}
		else
		{
			return substr( trim(sanitize_text_field( $data[ $key ] )), 0, $limit_lengh );
		}
	}

	/**
	 * Get whole form
	 */
	public static function get($fields=[], $data=false){

		$form = [];

		foreach ( $fields as $key )
		{
			$value = self::getField( $data, $key );
		}

		return $form;
	}

	/**
	 * Send form
	 */
	public static function send($to='admin', $subject='New message from website', $fields=[], $attachements=[], $email_id='email' ){

		if( !in_array($email_id, $fields) )
			$fields[] = $email_id;

		$form = self::get($fields);

		if ( is_email( $form[$email_id] ) )
		{
			if(!$to || $to='admin')
				$to = get_option( 'admin_email' );

			$body = $subject." :\n\n";
			$attachments = [];

			foreach ( $fields as $key ) {

				if ( !$form[$key] or !file_exists( $form[$key] ) )
				$body .= ($form[$key] ? ' - ' . $key . ' : ' . $form[$key] . "\n" : '');
			}

			// foreach ( $attachements as $attachement )
			// {
			// 	if ( $form[$attachement] and file_exists( $form[$attachement] ) )
			// 	{
			// 		$attachments[] = $form[$attachement];
			// 	}
			// }

			// if ( wp_mail( $to, $subject, $body, $attachments ) )
			$headers = 'From: plerouge@eleven-labs.com' . "\r\n";
			if ( mail( $to, $subject, $message, $headers ) )
				return $form;
			else
				return new \WP_Error('send_mail', "The server wasn't able to send the email.");

		}
		else
		{
			return new \WP_Error('invalid_email', "Invalid email address. Please type a valid email address.");
		}
	}
}
