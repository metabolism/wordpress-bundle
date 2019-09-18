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
	public $entity = 'image';

	public static $wp_upload_dir = false;
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

	private $compression = 90;
	private $show_meta = false;

	protected $src;

	/**
	 * Post constructor.
	 *
	 * @param null $id
	 */
	public function __construct($id = null) {

		global $_config;

		$this->compression = $_config->get('image.compression', 90);
		$this->show_meta = $_config->get('image.show_meta');

		if( isset($_REQUEST['debug']) && $_REQUEST['debug'] == 'image' && WP_ENV == 'dev' ){
			$this->ID = 0;
		}
		elseif( $data = $this->get($id) ){

			$this->import($data, false, 'post_');
		}
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
				$unserialized = is_string($value)?@unserialize($value):false;

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
		//imagefocus plugin support
		elseif( isset($post_meta['focus_point']) ){
			$this->focus_point = $post_meta['focus_point'];
		}

		if( file_exists($metadata['src']) )
			$post['mime_type'] = mime_content_type($metadata['src']);

		unset($post['post_category'], $post['tags_input'], $post['page_template'], $post['ancestors']);

		if( isset($post['mime_type']) && ($post['mime_type'] == 'image/svg+xml' || $this->mime_type == 'image/svg') )
			unset($metadata['meta'], $metadata['width'], $metadata['height']);

		if( $this->show_meta )
			$metadata['meta'] = $metadata['image_meta'];
		else
			$metadata['meta'] = false;

		unset($metadata['image_meta']);

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

		$image = $this->edit(['resize'=>[$w, $h]], $ext);

		if( is_array($params) && isset($params['name']) )
			$this->sizes[$params['name']] = $image;

		return $image;
	}


	/**
	 * @param array $params
	 * @param null $ext
	 * @return mixed
	 */
	public function edit($params, $ext=null){

		$abspath = str_replace(WP_FOLDER.'/..', '', $this->uploadDir('basedir'));

		$file = $this->process($params, $ext);

		$url = str_replace($abspath, $this->uploadDir('relative'), $file);

		if( is_array($params) && isset($params['name']) )
			$this->sizes[$params['name']] = $url;

		return $url;
	}


	/**
	 * Edit image using Intervention library
	 * @param array $params
	 * @param null $ext
	 * @return string
	 */
	private function process($params, $ext=null){

		//redefine ext if webp is not supported
		if( $ext === 'webp' && !function_exists('imagewebp'))
			$ext = null;

		//get size from params
		if( isset($params['resize']) ){

			$params['resize'] = (array)$params['resize'];
			$w = $params['resize'][0];
			$h = $params['resize'][1]??0;
		}
		else{

			$w = 800;
			$h = 600;
		}

		//return placeholder if image is empty
		if( empty($this->src) || !file_exists($this->src) )
			return $this->placeholder($w, $h);

		//remove focus point if invalid
		if( !is_array($this->focus_point) || !isset($this->focus_point['x'], $this->focus_point['y']) )
			$this->focus_point = false;

		//return if image is svg
		if( $this->mime_type == 'image/svg+xml' || $this->mime_type == 'image/svg' )
			return $this->src;

		// get src ext
		$src_ext = pathinfo($this->src, PATHINFO_EXTENSION);

		// define $dest_ext if not defined
		if( $ext == null )
			$ext = $src_ext;

		// get suffix
		// add width height
		$suffix = '-'.round($w).'x'.round($h);

		// add focus point
		if( $this->focus_point )
			$suffix .= '-c-'.round($this->focus_point['x']).'x'.round($this->focus_point['y']);

		// add params
		$filtered_params = $params;
		unset($filtered_params['resize']);

		if( count($filtered_params) )
			$suffix .= '-'.substr(md5(json_encode($filtered_params)), 0, 6);

		//append suffix to filename
		$dest = str_replace('.'.$src_ext, $suffix.'.'.$ext, $this->src);

		if( file_exists($dest) ){

			if( filemtime($dest) > filemtime($this->src) )
				return $dest;
			else
				unlink($dest);
		}

		try
		{
			$image = ImageManagerStatic::make($this->src);

			foreach ($params as $type=>$param){

				switch ($type){

					case 'resize':
						$this->crop($image, $w, $h);
						break;

					case 'insert':
						$image->insert(BASE_URI.$param[0], $param[1]??'top-left', $param[2]??0, $param[3]??0);
						break;

					case 'colorize':
						$image->colorize($param[0], $param[1], $param[2]);
						break;

					case 'blur':
						$image->blur($param[0]??1);
						break;

					case 'flip':
						$image->flip($param[0]??'v');
						break;

					case 'brightness':
						$image->brightness($param[0]);
						break;

					case 'invert':
						$image->invert();
						break;

					case 'mask':
						$image->mask(BASE_URI.$param[0], $param[1]??false);
						break;

					case 'gamma':
						$image->gamma($param[0]);
						break;

					case 'rotate':
						$image->rotate($param[0]);
						break;

					case 'text':
						$image->text($param[0], $param[1]??0, $param[2]??0, function($font) use($param) {

							$params = $param[3]??[];

							if( isset($params['file']) )
								$font->file(BASE_URI.$params['file']);

							if( isset($params['size']) )
								$font->size($params['size']);

							if( isset($params['color']) )
								$font->color($params['color']);

							if( isset($params['align']) )
								$font->align($params['align']);

							if( isset($params['valign']) )
								$font->valign($params['valign']);

							if( isset($params['angle']) )
								$font->angle($params['angle']);
						});

						break;

					case 'pixelate':
						$image->pixelate($param[0]);
						break;

					case 'greyscale':
						$image->greyscale();
						break;

					case 'limitColors':
						$image->limitColors($param[0], $param[1]??null);
						break;
				}
			}

			$image->save($dest, $this->compression);

			return $dest;
		}
		catch(\Exception $e)
		{
			return $e->getMessage();
		}
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
	 * @return \Twig\Markup
	 */
	public function toHTML($w, $h=0, $sources=false){

		if( empty($this->src) || !file_exists($this->src) ){

			$html = '<picture>';
			if( $sources && is_array($sources) ){

				foreach ($sources as $media=>$size)
					$html .='	<source media="('.$media.')"  srcset="'.$this->placeholder($size[0], $size[1] ?? 0).'">';
			}

			$html .='	<source srcset="'.$this->placeholder($w, $h).'">';
			$html .= '<img src="'.$this->placeholder($w, $h).'">';
			$html .='</picture>';

			return new \Twig\Markup($html, 'UTF-8');
		}

		$ext = function_exists('imagewebp') ? 'webp' : null;
		$mime = function_exists('imagewebp') ? 'image/webp' : $this->mime_type;

		$html = '<picture>';

		if($this->mime_type == 'image/svg+xml' || $this->mime_type == 'image/svg' ){

			$html .= '<img src="'.$this->edit(['resize'=>[$w, $h]]).'" alt="'.$this->alt.'">';
		}
		else{

			if( $sources && is_array($sources) ){

				foreach ($sources as $media=>$size){

					if( $ext == 'webp' )
						$html .='	<source media="('.$media.')"  srcset="'.$this->edit(['resize'=>$size], $ext).'" type="'.$mime.'">';

					$html .='	<source media="('.$media.')"  srcset="'.$this->edit(['resize'=>$size]).'" type="'.$this->mime_type.'">';
				}
			}

			if( $ext == 'webp' )
				$html .='	<source srcset="'.$this->edit(['resize'=>[$w, $h]], $ext).'" type="'.$mime.'">';

			$html .= '<img src="'.$this->edit(['resize'=>[$w, $h]]).'" alt="'.$this->alt.'">';
		}

		$html .='</picture>';

		return new \Twig\Markup($html, 'UTF-8');
	}


	/**
	 * @param $w
	 * @param int $h
	 * @param null $ext
	 * @return void
	 */
	protected function crop(&$image, $w, $h=0){

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

			$tmp = tempnam("/tmp", "II");

			$image->save($tmp, 100);

			$image = ImageManagerStatic::make($tmp);

			$image->fit($w, $h);
		}
		else{

			$image->fit($w, $h);
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
