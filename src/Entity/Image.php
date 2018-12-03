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


	protected function uploadDir($field)
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

		if( empty($metadata) || !isset($metadata['file'], $metadata['image_meta']) )
			return false;

		$metadata['src']  = $this->uploadDir('basedir').'/'.$metadata['file'];
		$metadata['src']  = str_replace(WP_FOLDER.'/..', '', $metadata['src']);

		$metadata['file'] = $this->uploadDir('relative').'/'.$metadata['file'];
		$metadata['meta'] = $metadata['image_meta'];
		$metadata['alt']  = trim(strip_tags(get_post_meta($id, '_wp_attachment_image_alt', true)));

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

		if( isset($post['mime_type']) && $post['mime_type'] == 'image/svg+xml')
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

		$image_file = $this->crop($w, $h);
		$image = str_replace($abspath, $this->uploadDir('relative'), $image_file);

		if( $name )
			$this->sizes[$name] = $image;

		return $image;
	}


	protected function crop($w, $h = 0)
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

			if(!$w){

				$image->resizeToHeight($h, true);
			}
			elseif(!$h){

				$image->resizeToWidth($w, true);
			}
			elseif($this->focus_point){

				$src_width = $image->getSourceWidth();
				$src_height = $image->getSourceHeight();
				$src_ratio = $src_width/$src_height;
				$dest_ratio = $w/$h;

				$ratio_height = $src_height/$h;
				$ratio_width = $src_width/$w;

				if( $dest_ratio < 1)
				{
					$dest_width = $w*$ratio_height;
					$dest_height = $src_height;
				}
				else
				{
					$dest_width = $src_width;
					$dest_height = $h*$ratio_width;
				}

				if ($ratio_height < $ratio_width) {

					list($cropX1, $cropX2) = $this->calculateCrop($src_width, $dest_width, $this->focus_point['x']/100);
					$cropY1 = 0;
					$cropY2 = $src_height;
				} else {

					list($cropY1, $cropY2) = $this->calculateCrop($src_height, $dest_height, $this->focus_point['y']/100);
					$cropX1 = 0;
					$cropX2 = $src_width;
				}

				$image->freecrop($cropX2 - $cropX1, $cropY2 - $cropY1, $cropX1, $cropY1);
				$image->save($dest, null, 100);

				$image = new ImageResize($dest);
				$image->resize($w, $h, true);
			}
			else{

				$image->crop($w, $h, true);
			}

			$image->save($dest, null,  $this->quality_jpg);

			return $dest;
		}
		catch(ImageResizeException $e)
		{
			return $e->getMessage();
		}
	}

	protected function calculateCrop($origSize, $newSize, $focalFactor) {

		$focalPoint = $focalFactor * $origSize;
		$cropStart = $focalPoint - $newSize / 2;
		$cropEnd = $cropStart + $newSize;

		if ($cropStart < 0) {
			$cropEnd -= $cropStart;
			$cropStart = 0;
		} else if ($cropEnd > $origSize) {
			$cropStart -= ($cropEnd - $origSize);
			$cropEnd = $origSize;
		}

		return array(ceil($cropStart), ceil($cropEnd));
	}
}
