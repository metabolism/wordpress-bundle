<?php

namespace Metabolism\WordpressLoader\Helper;

use Metabolism\WordpressLoader\Plugin\MediaPlugin as Media;


/**
 * Class Metabolism\WordpressLoader Framework
 */
class FormHelper {

	/**
	 * Get request parameter
	 */
	public static function get( $key, $limit_lengh=500 )
	{
		if( isset($_FILES[$key]))
		{
			$upload = Media::upload($key, ['image/jpeg', 'image/gif', 'image/png', 'application/pdf', 'application/zip']);

			if( isset($upload['error']))
				return false;

			return $upload['filename'];
		}
		elseif ( !isset( $_REQUEST[ $key ] ) )
		{
			return false;
		}
		else
		{
			return substr( trim(sanitize_text_field( $_REQUEST[ $key ] )), 0, $limit_lengh );
		}
	}


	/**
	 * Serialize REQUEST data
	 */
	public static function meetRequirement( $id, $value='', $validation = [] ) {

		//todo check for type
		$id = str_replace('[]', '', $id);
		return isset( $validation[$id] ) && ( !isset($validation[$id]['required']) || !empty($value) );
	}


	/**
	 * Serialize REQUEST data
	 */
	public static function serialize( $key = 'data', $validation = [], $limit_lengh=500 ) {

		$form_data = isset($_REQUEST[$key])?$_REQUEST[$key]:[];
		$data = [];

		foreach ($form_data as $field)
		{
			$id    = trim( sanitize_text_field($field['name']) );
			$value = substr( trim( sanitize_text_field($field['value'])), 0, $limit_lengh );

			if( strpos($id, '[]') !== false )
				$data[str_replace('[]', '', $id)][] = $value;
			else
				$data[$id] = $value;

			if( !empty($validation) && !self::meetRequirement($id, $value, $validation) )
				return false;
		}

		return $data;
	}

	/**
	 * Quickly send form
	 */
	public static function send($fields=[], $files=[], $subject='New message from website', $email_id='email', $delete_attachements=true){

		$email = self::get( $email_id );

		if ( $email && is_email( $email ) )
		{
			$body = $subject." :\n\n";

			foreach ( $fields as $key )
			{
				$value = self::get( $key );
				$body  .= ( $value ? ' - ' . $key . ' : ' . $value . "\n" : '' );
			}

			$attachments = [];

			foreach ( $files as $file )
			{
				$file = self::get( $file );

				if ( file_exists( WP_CONTENT_DIR.$file ) )
					$attachments[] = WP_CONTENT_DIR.$file;
			}

			if ( wp_mail( get_option( 'admin_email' ), $subject, $body, $attachments ) )
			{
				if( $delete_attachements )
				{
					foreach ( $attachments as $file )
						@unlink($file);
				}

				return true;
			}
			else
				return ['error' => 2, 'message' => "Sorry, the server wasn't able to complete this request"];

		}
		else
		{
			return ['error' => 1, 'message' => "Invalid email address. Please type a valid email address."];
		}
	}
}
