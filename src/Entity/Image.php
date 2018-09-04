<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Metabolism\WordpressBundle\Entity;

use Gumlet\ImageResize;

/**
 * Class Image
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Image extends Entity
{
	public static $wp_upload_dir = false;

	public $focus_point = false;
	private $quality_jpg = 90;

	protected $src;

	public $sizes = [];

	/**
	 * Post constructor.
	 *
	 * @param null $id
	 */
	public function __construct($id = null) {

		global $_config;
		$this->quality_jpg = $_config->get('jpeg_quality', 90);

		if( $data = $this->get($id) )
			$this->import($data, false, 'post_');
	}


	private function uploadDir($field)
	{
		if ( !self::$wp_upload_dir )
			self::$wp_upload_dir = wp_upload_dir();

		return self::$wp_upload_dir[$field];
	}


	/**
	 * Remove useless data
	 */
	protected function get($id)
	{
		$metadata = wp_get_attachment_metadata($id);
		$post = get_post($id, ARRAY_A);
		$post_meta = get_post_meta($id);

		if( !$post || is_wp_error($post) )
			return false;

		if( !empty($metadata) )
		{
			$metadata['src']  = $this->uploadDir('basedir').'/'.$metadata['file'];
			$metadata['src']  = str_replace(WP_FOLDER.'/..', '', $metadata['src']);

			$metadata['file'] = $this->uploadDir('relative').'/'.$metadata['file'];
			$metadata['meta'] = $metadata['image_meta'];
			$metadata['alt']  = trim(strip_tags(get_post_meta($id, '_wp_attachment_image_alt', true)));
		}

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

		if( !empty($metadata) )
			unset($metadata['sizes'], $metadata['image_meta']);

		if( file_exists($metadata['src']) )
			$post['mime_type'] = mime_content_type($metadata['src']);

		unset($post['post_category'], $post['tags_input'], $post['page_template'], $post['ancestors']);

		if( $post['mime_type'] == 'image/svg+xml')
			unset($metadata['meta'],$metadata['width'],$metadata['height']);

		if( is_array($metadata) )
			return array_merge($post, $metadata);
		else
			return $post;
	}


	public function getSrc(){
		return $this->src;
	}


	public function getFileContent(){

		if( file_exists($this->src) )
			return file_get_contents($this->src);
		else
			return 'File does not exist';
	}


	public function resize($w, $h = 0, $name=false){

		$abspath = $this->uploadDir('basedir');
		$abspath = str_replace(WP_FOLDER.'/..', '', $abspath);

		$image_file = $this->_resize($w, $h);
		$image = str_replace($abspath, $this->uploadDir('relative'), $image_file);

		if( $name )
			$this->sizes[$name] = $image;

		return $image;
	}


	private function _resize($w, $h = 0)
	{
		if( !is_array($this->focus_point) || !isset($this->focus_point['x'], $this->focus_point['y']) )
			$this->focus_point = false;

		if( !file_exists($this->src) )
			return 'File does not exist';

		$ext = pathinfo($this->src, PATHINFO_EXTENSION);

		if( $ext == 'svg' )
			return $this->src;

		if( $this->focus_point )
			$dest = str_replace('.'.$ext, '-'.round($w).'x'.round($h).'-c-'.round($this->focus_point['x']).'x'.round($this->focus_point['y']).'.' . $ext, $this->src);
		else
			$dest = str_replace('.'.$ext, '-'.round($w).'x'.round($h).'.' . $ext, $this->src);

		if( file_exists($dest) ){

			if( filemtime($dest) > filemtime($this->src) )
				return  $dest;
			else
				unlink($dest);
		}

		try
		{
			$image = new ImageResize($this->src);
			$image->quality_jpg = $this->quality_jpg;

			if(!$w)
				$image->resizeToHeight($h, true);
			elseif(!$h)
				$image->resizeToWidth($w, true);
			elseif($this->focus_point)
				$image->freecrop($w, $h, $this->focus_point['x'], $this->focus_point['y']);
			else
				$image->crop($w, $h, true);

			$image->save($dest);

			return $dest;
		}
		catch(ImageResizeException $e)
		{
			return $e->getMessage();
		}
	}
}
