<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Metabolism\WordpressBundle\Entity;

/**
 * Class Post
 * @see \Timber\Term
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Image extends Entity
{
	public $type;
	public static $wp_upload_dir = false;


	/**
	 * Post constructor.
	 *
	 * @param null $id
	 */
	public function __construct($id = null) {

		$data = $this->get($id);
		$this->import($data);
	}


	public function uploadDir()
	{
		if ( !self::$wp_upload_dir ){

			$wp_upload_dir = wp_upload_dir();
			self::$wp_upload_dir = $wp_upload_dir['relative'];
		}

		return self::$wp_upload_dir;
	}


	/**
	 * Remove useless data
	 */
	protected function get($id)
	{
		$metadata = wp_get_attachment_metadata($id);

		$metadata['src']  = BASE_PATH.$this->uploadDir().'/'.$metadata['file'];
		$metadata['meta'] = $metadata['image_meta'];
		$metadata['alt']  = trim(strip_tags(get_post_meta($id, '_wp_attachment_image_alt', true)));

		$post = get_post($id, ARRAY_A);
		$post_meta = get_post_meta($id);

		foreach($post_meta as $key=>$value)
		{
			if( in_array($key, ['_wp_attached_file', '_wp_attachment_metadata']) )
				continue;

			if($key == '_wp_attachment_image_alt')
			{
				$post['alt'] = trim($value[0]);
			}
			else
			{
				$value = (is_array($value) and count($value)==1) ? $value[0] : $value;
				$unserialized = @unserialize($value);
				$post[$key] = $unserialized?$unserialized:$value;
			}
		}


		unset($metadata['sizes'], $metadata['image_meta'], $post['post_category'], $post['tags_input'], $post['page_template'], $post['ancestors']);

		return array_merge($post, $metadata);
	}
}
