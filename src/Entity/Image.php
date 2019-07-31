<?php

namespace Metabolism\WordpressBundle\Entity;

use Intervention\Image\ImageManagerStatic;

/**
 * Class Image
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Image extends Entity
{
	public static $wp_upload_dir = false;

	private $compression = 90;

	protected $src;

	public $focus_point = false;
	public $file;
	public $meta;
	public $alt;
	public $mime_type;
	public $width;
	public $height;
	public $date;
	public $date_gmt;
	public $modified;
	public $modified_gmt;
	public $title;

	public $sizes = [];

	/**
	 * Post constructor.
	 *
	 * @param null $id
	 */
	public function __construct($id = null) {

		global $_config;
		$this->compression = $_config->get('image.compression', 90);

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
	 * @param $id
	 * @return array|bool|\WP_Post|null
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
			if( in_array($key, ['_wp_attached_file', '_wp_attachment_metadata', '_wpsmartcrop_enabled', '_wpsmartcrop_image_focus']) )
				continue;

			if($key == '_wp_attachment_image_alt')
			{
				$post['alt'] = trim($value[0]);
			}
			else
			{
				$value = (is_array($value) && count($value)==1) ? $value[0] : $value;
				$unserialized = @unserialize($value);

				if( substr($key, 0, 1) == '_')
					$key = substr($key, 1);

				$post[$key] = $unserialized?$unserialized:$value;
			}
		}

		//wpsmartcrop plugin support
		if( isset($post_meta['_wpsmartcrop_enabled'], $post_meta['_wpsmartcrop_image_focus']) && $post_meta['_wpsmartcrop_enabled'][0] ){
			$focus_point =  @unserialize($post_meta['_wpsmartcrop_image_focus'][0]);
			$this->focus_point = ['x'=>$focus_point['left'], 'y'=>$focus_point['top']];
		}

		if( !empty($metadata) )
			unset($metadata['sizes'], $metadata['image_meta']);

		if( file_exists($metadata['src']) )
			$post['mime_type'] = mime_content_type($metadata['src']);

		unset($post['post_category'], $post['tags_input'], $post['page_template'], $post['ancestors']);

		if( isset($post['mime_type']) && ($post['mime_type'] == 'image/svg+xml' || $this->mime_type == 'image/svg') )
			unset($metadata['meta'],$metadata['width'],$metadata['height']);

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


	/**
	 * @param $w
	 * @param int $h
	 * @param null $ext
	 * @param bool $params
	 * @return mixed
	 */
	public function resize($w, $h = 0, $ext=null, $params=false){

		if( $ext === 'webp' && !function_exists('imagewebp'))
			$ext = null;

		$abspath = $this->uploadDir('basedir');
		$abspath = str_replace(WP_FOLDER.'/..', '', $abspath);

		$image_file = $this->crop($w, $h, $ext, $params);
		$image = str_replace($abspath, $this->uploadDir('relative'), $image_file);

		if( is_array($params) && isset($params['name']) )
			$this->sizes[$params['name']] = $image;

		return $image;
	}


	/**
	 * @param int $w
	 * @param int $h
	 * @return string
	 */
	private function placeholder($w, $h=0){

		$width = $w == 0 ? 1280 : $w;
		$height = $h > 0 ? 'x'.$h : '';

		return 'https://via.placeholder.com/'.$width.$height.'.jpg';
	}

	/**
	 * @param $w
	 * @param int $h
	 * @param bool $sources
	 * @param bool $params
	 * @return \Twig\Markup
	 */
	public function toHTML($w, $h=0, $sources=false, $params=false){

		if( empty($this->src) || !file_exists($this->src) )
			return new \Twig\Markup('<img src="'.$this->placeholder($w, $h).'" alt="image not found or empty">', 'UTF-8');;

		$ext = function_exists('imagewebp') ? 'webp' : null;
		$mime = function_exists('imagewebp') ? 'image/webp' : $this->mime_type;

		$html = '<picture>';

		if($this->mime_type == 'image/svg+xml' || $this->mime_type == 'image/svg' || !$sources ){

			$html .= '<img src="'.$this->resize($w, $h, null, $params).'" alt="'.$this->alt.'">';
		}
		else{

			if( $sources && is_array($sources) ){

				foreach ($sources as $media=>$size){
					$html .='	<source media="('.$media.')"  srcset="'.$this->resize($size[0], $size[1] ?? 0, $ext, $params).'" type="'.$mime.'">';
					if( $ext == 'webp' )
						$html .='	<source media="('.$media.')"  srcset="'.$this->resize($size[0], $size[1] ?? 0, null, $params).'" type="'.$this->mime_type.'">';
				}
			}

			$html .='	<source srcset="'.$this->resize($w, $h, $ext, $params).'" type="'.$mime.'">';
			$html .= '<img src="'.$this->resize($w, $h, null, $params).'" alt="'.$this->alt.'">';
		}

		$html .='</picture>';

		return new \Twig\Markup($html, 'UTF-8');
	}


	/**
	 * @param $w
	 * @param int $h
	 * @param null $ext
	 * @param bool $params
	 * @return mixed|string
	 */
	protected function crop($w, $h=0, $ext=null, $params=false){

		if( empty($this->src) || !file_exists($this->src) )
			return $this->placeholder($w, $h);

		if( !is_array($this->focus_point) || !isset($this->focus_point['x'], $this->focus_point['y']) )
			$this->focus_point = false;

		$src_ext = pathinfo($this->src, PATHINFO_EXTENSION);

		if( $this->mime_type == 'image/svg+xml' || $this->mime_type == 'image/svg' )
			return $this->src;

		if( $ext == null )
			$ext = $src_ext;

		$extrafilename = $params ? '-'.substr(md5(json_encode($params)), 0, 6) : '';

		if( $this->focus_point )
			$dest = str_replace('.'.$src_ext, '-'.round($w).'x'.round($h).'-c-'.round($this->focus_point['x']).'x'.round($this->focus_point['y']).$extrafilename.'.' . $ext, $this->src);
		else
			$dest = str_replace('.'.$src_ext, '-'.round($w).'x'.round($h).$extrafilename.'.' . $ext, $this->src);

		if( file_exists($dest) ){

			if( filemtime($dest) > filemtime($this->src) )
				return  $dest;
			else
				unlink($dest);
		}

		try
		{
			$image = ImageManagerStatic::make($this->src);

			if(!$w){

				$image->resize(null, $h, function ($constraint) {
					$constraint->aspectRatio();
				});
			}
			elseif(!$h){

				$image->resize($w, null, function ($constraint) {
					$constraint->aspectRatio();
				});
			}
			elseif($this->focus_point){

				$src_width = $image->getWidth();
				$src_height = $image->getHeight();
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

				$image->crop($cropX2 - $cropX1, $cropY2 - $cropY1, $cropX1, $cropY1);
				$image->save($dest, 100);

				$image = ImageManagerStatic::make($dest);

				$image->fit($w, $h);
			}
			else{

				$image->fit($w, $h);
			}

			if($params && is_array($params) ){

				if( isset($params['colorize']) && count($params['colorize']) === 3 )
					$image->colorize($params['colorize'][0], $params['colorize'][1], $params['colorize'][2]);

				if( isset($params['blur']) )
					$image->blur($params['blur']);

				if( isset($params['brightness']) )
					$image->brightness($params['brightness']);

				if( isset($params['gamma']) )
					$image->gamma($params['gamma']);

				if( isset($params['pixelate']) )
					$image->pixelate($params['pixelate']);

				if( isset($params['greyscale']) )
					$image->greyscale();

				if( isset($params['limitColors']) && count($params['limitColors']) === 2 )
					$image->limitColors($params['limitColors'][0], $params['limitColors'][1]);
			}

			$image->save($dest, $this->compression);

			return $dest;
		}
		catch(Exception $e)
		{
			return $e->getMessage();
		}
	}

	/**
	 * @param $origSize
	 * @param $newSize
	 * @param $focalFactor
	 * @return array
	 */
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
