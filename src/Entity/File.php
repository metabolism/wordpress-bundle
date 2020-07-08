<?php

namespace Metabolism\WordpressBundle\Entity;

/**
 * Class File
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class File extends Entity
{
	public $entity = 'file';

	public static $wp_upload_dir = false;

	public $file;
	public $link;
	public $url;
	public $mime_type;
	public $extension;
	public $date;
	public $date_gmt;
	public $modified;
	public $modified_gmt;
	public $title;
	public $caption;
	public $description;
	public $size;

	private $args = [];

	protected $src;

	/**
	 * Post constructor.
	 *
	 * @param int|string $id
	 * @param array $args
	 */
	public function __construct($id=null, $args=[]) {

		global $_config;

		$this->args = $args;

		if( $data = $this->get($id) )
			$this->import($data, false, 'post_');
	}


	protected function uploadDir($field)
	{
		if ( !self::$wp_upload_dir )
			self::$wp_upload_dir = wp_upload_dir();

		return self::$wp_upload_dir[$field];
	}


	/**
	 * Return true if file exists
	 */
	public function exist()
	{
		return $this->ID ===  0 || file_exists( $this->src );
	}


	/**
	 * Remove useless data
	 * @param $id
	 * @return array|bool|\WP_Post|null
	 */
	protected function get($id)
	{
		$post = $metadata = false;

		if( is_numeric($id) ){

			$metadata = wp_get_attachment_metadata($id);
			$post = get_post($id, ARRAY_A);
			$post_meta = get_post_meta($id);

			if( !$post || is_wp_error($post) )
				return false;

			if( !is_array($metadata) )
				$metadata = [];

			$metadata['file'] = get_post_meta($id, '_wp_attached_file', true);

			if( !$metadata['file'] )
				return false;

			$metadata['src']  = $this->uploadDir('basedir').'/'.$metadata['file'];
			$metadata['src']  = str_replace(WP_FOLDER.'/..', '', $metadata['src']);

			if( !file_exists($metadata['src']) )
				return false;

			$metadata['file'] = $this->uploadDir('relative').'/'.$metadata['file'];

			$post['mime_type'] = mime_content_type($metadata['src']);
			$post['extension'] = pathinfo($metadata['src'], PATHINFO_EXTENSION);
			$post['caption'] = $post['post_excerpt'];
			$post['description'] = $post['post_content'];
			$post['size'] = filesize($metadata['src']);
			$post['link'] = $post['url'] = home_url($metadata['file']);

			unset($post['post_category'], $post['tags_input'], $post['page_template'], $post['ancestors']);
		}
		elseif( is_string($id) ){

			$filename = BASE_URI.$id;

			if( !file_exists( $filename) )
				return false;

			$post = $post_meta = [];

			$image_size = getimagesize($filename);

			$metadata = [
				'src' => $filename,
				'file' => str_replace(PUBLIC_DIR, '', $id),
				'link' => home_url(str_replace(PUBLIC_DIR, '', $id)),
				'size' => filesize($filename),
				'mime_type' => $image_size['mime'],
				'post_title' => str_replace('_', ' ', pathinfo($filename, PATHINFO_FILENAME)),
				'post_date' => filemtime($filename),
				'post_date_gmt' => date("Y-m-d H:i:s", filemtime($filename)),
				'post_modified' => filectime($filename),
				'post_modified_gmt' => date("Y-m-d H:i:s", filectime($filename))
			];
		}

		if( is_array($metadata) )
			return array_merge($post, $metadata);
		else
			return $post;
	}


	public function getSrc(){
		return $this->src;
	}


	/**
	 * @return false|string
	 */
	public function getFileContent(){

		if( file_exists($this->src) )
			return file_get_contents($this->src);
		else
			return 'File does not exist';
	}
}
