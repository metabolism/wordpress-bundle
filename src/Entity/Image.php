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

    public $caption;
    public $description;
    public $file;
	public $width;
	public $height;
	public $mime_type;
    public $sizes = [];
    public $title;
    public $alt;

    protected $link;
    protected $extension;
    protected $size;
    protected $focus_point;
    protected $metadata;
    protected $date;
    protected $date_gmt;
    protected $modified;
    protected $modified_gmt;
    protected $src;
    protected $post;

    protected $compression;
    protected $args;

    public function __toString()
    {
        return $this->getLink();
    }

	/**
	 * Post constructor.
	 *
	 * @param int|string $id
	 * @param array $args
	 */
	public function __construct($id=null, $args=[])
	{
        global $_config;

		$this->args = $args;

		if (isset($this->args['compression']))
			$this->compression = $this->args['compression'];
		else
			$this->compression = $_config ? $_config->get('image.compression', 90) : 90;

		if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 'image' && WP_ENV == 'dev') {

			$this->ID = 0;
		}
		else {

			$this->get($id);
		}
	}


    /**
     * @param $field
     * @return mixed|string
     */
    protected function uploadDir($field)
	{
		if ( !self::$wp_upload_dir )
			self::$wp_upload_dir = wp_upload_dir();

		return self::$wp_upload_dir[$field]??'';
	}


	/**
	 * Return true if file exists
	 */
	public function exist()
	{
		return $this->ID ===  0 || file_exists( $this->src );
	}


    /**
     * Download image
     *
     * @param $url
     * @param int $ttl
     * @return false|string
     */
    private function getRemoteImage($url, $ttl=false){

        if( !$ttl )
            $ttl = 2592000;

        $basename = strtolower(pathinfo($url, PATHINFO_BASENAME));
        $folder = '/cache/'.md5($url);
        $filepath = $folder.'/'.$basename;

        $folderpath =  $this->uploadDir('basedir').$folder;
        $filename =  $this->uploadDir('basedir').$filepath;
        $relative_filename =  $this->uploadDir('relative').$filepath;

        if( file_exists($filename) ){

            $cachetime = filemtime($filename) + $ttl;

            if( $cachetime > time() )
                return $relative_filename;
        }

        $tmpfile = tempnam ('/tmp', 'img-');
        file_put_contents($tmpfile, file_get_contents($url));

        $mime_type = mime_content_type($tmpfile);

        if( !in_array($mime_type, ['image/jpeg', 'image/jpg', 'image/gif', 'image/png']) ){

            unlink($tmpfile);
            return false;
        }

        if( !is_dir($folderpath) )
            mkdir($folderpath, 0755, true);

        rename($tmpfile, $filename);

        return $relative_filename;
    }


	/**
	 * Get image data
     *
	 * @param $id
	 * @return void
	 */
	protected function get($id)
	{
		if( is_numeric($id) ){

			if( $post = get_post($id) ) {

                if( is_wp_error($post) || !$post )
					return;

				$post_meta = get_post_meta($id);

				$attachment_metadata = apply_filters( 'wp_get_attachment_metadata', maybe_unserialize($post_meta['_wp_attachment_metadata'][0]??''), $id );

				if( !$attachment_metadata ){

					if( $post->post_mime_type != 'image/svg' && $post->post_mime_type != 'image/svg+xml' )
						return;

					$attachment_metadata = [
						'file' => $post_meta['_wp_attached_file'][0],
						'width' =>  '',
						'height' =>  '',
						'image_meta' =>  []
					];

					$this->focus_point = false;
				}

				$filename = $this->uploadDir('basedir').'/'.$attachment_metadata['file'];

                if( !file_exists( $filename) )
					return;

                $this->ID = $post->ID;
				$this->caption = $post->post_excerpt;
				$this->description = $post->post_content;
				$this->file = $this->uploadDir('relative').'/'.$attachment_metadata['file'];
				$this->src = $filename;
				$this->post = $post;

				$this->title = $post->post_title;
                $this->alt = trim(strip_tags($post_meta['_wp_attachment_image_alt'][0]??''));

				$this->width = $attachment_metadata['width'];
				$this->height = $attachment_metadata['height'];
				$this->metadata = $attachment_metadata['image_meta'];
				$this->mime_type = $post->post_mime_type;
            }
		}
		else{

            if( substr($id,0, 7) == 'http://' || substr($id,0, 8) == 'https://' )
                $id = $this->getRemoteImage($id, $this->args['ttl']??false);

            $filename = BASE_URI.PUBLIC_DIR.$id;

            if( !$filename || !file_exists( $filename) || is_dir( $filename ) )
                return;

			$this->ID = 0;
			$this->file = $id;
			$this->src = $filename;
			$this->post = false;

            if( isset($this->args['title']) )
                $this->title = $this->args['title'];
            else
                $this->title = str_replace('_', ' ', pathinfo($filename, PATHINFO_FILENAME));

            if( isset($this->args['alt']) )
                $this->alt = $this->args['alt'];

			$image_size = getimagesize($filename);
			$this->width = $image_size[0]??false;
			$this->height = $image_size[1]??false;

			$this->metadata = false;
			$this->mime_type = mime_content_type($filename);
		}
    }

	/**
	 * @return string|null
	 */
	public function getLink(){

        if( is_null($this->link) && $this->src )
            $this->link = home_url($this->file);

        return $this->link;
    }

	/**
	 * @return string
	 */
	public function getExtension(){

        if( is_null($this->extension) && $this->src )
            $this->extension = pathinfo($this->src, PATHINFO_EXTENSION);

        return $this->extension;
    }

	/**
	 * @return float|int
	 */
	public function getSize(){

        if( is_null($this->size) && $this->src )
            $this->size = filesize($this->src)/1024;

        return $this->size;
    }

	/**
	 * @return array|mixed
	 */
	public function getFocusPoint(){

		if( is_null($this->focus_point) && $this->ID ){

			$post_meta = get_post_meta($this->ID);

			if( isset($post_meta['_wpsmartcrop_enabled'], $post_meta['_wpsmartcrop_image_focus']) && $post_meta['_wpsmartcrop_enabled'][0] ){
				$focus_point =  @unserialize($post_meta['_wpsmartcrop_image_focus'][0]);
				$this->focus_point = ['x'=>$focus_point['left'], 'y'=>$focus_point['top']];
			}
			//imagefocus plugin support
			elseif( isset($post_meta['focus_point']) ){
				$this->focus_point = $post_meta['focus_point'];
			}
		}

		return $this->focus_point;
	}

	/**
	 * @param $format
	 * @return false|int|mixed|null
	 */
	public function getDate($format=true){

		if( is_null($this->date) && $format ){

			if( $this->post )
				$this->date = $this->formatDate($this->post->post_date);
			else
				$this->date = $this->formatDate(filemtime($this->src));
		}

        if( $format )
            return $this->date;
        else
            return $this->post ? $this->post->post_date : filemtime($this->src);
	}

	/**
	 * @param $format
	 * @return false|int|mixed|null
	 */
	public function getModified($format=true){

		if( is_null($this->modified) && $format ){

			if( $this->post )
				$this->modified = $this->formatDate($this->post->post_modified);
			else
				$this->modified = $this->formatDate(filectime($this->src));
		}

        if( $format )
            return $this->modified;
        else
            return $this->post ? $this->post->post_modified : filectime($this->src);
	}

	/**
	 * @param $format
	 * @return false|int|mixed|null
	 */
	public function getDateGmt($format=true){

		if( is_null($this->date_gmt) && $format ){

			if( $this->post )
				$this->date_gmt = $this->formatDate($this->post->post_date_gmt);
			else
				$this->date_gmt = $this->formatDate(filemtime($this->src));
		}

        if( $format )
            return $this->date_gmt;
        else
            return $this->post ? $this->post->post_date_gmt : filemtime($this->src);
	}

	/**
	 * @param $format
	 * @return false|int|mixed|null
	 */
	public function getModifiedGmt($format=true){

		if( is_null($this->modified_gmt) && $format ){

			if( $this->post )
				$this->modified_gmt = $this->formatDate($this->post->post_modified_gmt);
			else
				$this->modified_gmt = $this->formatDate(filectime($this->src));
		}

        if( $format )
            return $this->modified_gmt;
        else
            return $this->post ? $this->post->post_modified_gmt : filectime($this->src);
	}

	/**
	 * @return array|false
	 */
	public function getMetadata(){

		if(is_null($this->metadata) && function_exists('exif_read_data'))
			$this->metadata = @exif_read_data($this->src);

		return $this->metadata;
	}


	/**
	 * @return mixed
	 */
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
	 * @param array $params
	 * @return mixed
	 */
	public function resize($w, $h = 0, $ext=null, $params=[]){

		$name = is_array($params) && isset($params['name']) ? $params['name'] : false;

		$params = array_merge(['resize'=>[$w, $h]], $params);
		unset($params['name']);

		$image = $this->edit($params, $ext);

		if( is_null($ext) )
			$ext = pathinfo($image, PATHINFO_EXTENSION);

		if( $name ){

			unset($params['resize']);

			$src = BASE_URI.PUBLIC_DIR.$image;
			$image_info = file_exists($src)?getimagesize($src):[0,0, 'mime'=>''];

			$this->sizes[$name][] = array_merge([
				'file'=>$image,
				'extension'=>$ext,
				'mime-type'=>$image_info['mime'],
				'width'=>$image_info[0],
				'height'=>$image_info[1]
			], $params);
		}

		return $image;
	}


	/**
	 * @param array $params
	 * @param null $ext
	 * @return string
	 */
	public function edit($params, $ext=null){

		$file = $this->process($params, $ext);

        if( apply_filters('wp_make_url_relative', true) )
            $url = str_replace($this->uploadDir('basedir'), $this->uploadDir('relative'), $file);
        else
            $url = str_replace($this->uploadDir('basedir'), $this->uploadDir('baseurl'), $file);

		return str_replace(BASE_URI.PUBLIC_DIR, '', $url);
	}


	/**
	 * Edit image using Intervention library
	 * @param array $params
	 * @param null $ext
	 * @return string
	 */
	private function process($params, $ext=null){

		if( $this->src && !in_array($this->getExtension(), ['jpg','jpeg','png','gif','webp']) )
			return $this->src;

		$this->getFocusPoint();

		//redefine ext if webp is not supported
		if( $ext === 'webp' && !function_exists('imagewebp'))
			$ext = null;

		//get size from params
		if( isset($params['resize']) ){

			$params['resize'] = (array)$params['resize'];
			$w = $params['resize'][0];
			$h = count($params['resize'])>1?$params['resize'][1]:0;
		}
		else{

			if( file_exists($this->src) && $image_size = getimagesize($this->src) ){
				$w = $image_size[0];
				$h = $image_size[1];
			}
			else{
				$w = 800;
				$h = 600;
			}
		}


		if( isset($params['gcd']) ){

			if( ($w == 0 || $h == 0) && file_exists($this->src) && $image_size = getimagesize($this->src) ){

				$ratio = $image_size[0]/$image_size[1];

				if( $w == 0 )
					$w = round($h*$ratio);
				else
					$h = round($w/$ratio);
			}

			$w = round($w/10);
			$h = round($h/10);
		}


		//return placeholder if image is empty
		if( empty($this->src) || !file_exists($this->src) )
			return $this->placeholder($w, $h);

		//remove focus point if invalid
		if( !is_array($this->focus_point) || !isset($this->focus_point['x'], $this->focus_point['y']) )
			$this->focus_point = false;

		//return if image is svg or gif
		if( $this->mime_type == 'image/svg+xml' || $this->mime_type == 'image/svg' || $this->mime_type == 'image/gif' )
			return $this->src;

		// get src ext
		$src_ext = pathinfo($this->src, PATHINFO_EXTENSION);

		// define $dest_ext if not defined
		if( $ext == null )
			$ext = $src_ext;

		// get suffix
		// add width height
        if( is_string($h))
            $h = intval($h);

        if( is_string($w))
            $w = intval($w);

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

		if( isset($this->args['path']) ){

			$path = BASE_URI.$this->args['path'];

			if( !is_dir($path) )
				mkdir($path, 0755, true);

			$dest = $path.'/'.pathinfo($dest, PATHINFO_BASENAME);
		}

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

				$param = (array)$param;

				switch ($type){

					case 'resize':
						$this->crop($image, $w, $h);
						break;

					case 'insert':
						$image->insert(BASE_URI.$param[0], count($param)>1?$param[1]:'top-left', count($param)>2?$param[2]:0, count($param)>3?$param[3]:0);
						break;

					case 'colorize':
						$image->colorize($param[0], $param[1], $param[2]);
						break;

					case 'blur':
						$image->blur(count($param)?$param[0]:1);
						break;

					case 'flip':
						$image->flip(count($param)?$param[0]:'v');
						break;

					case 'brightness':
						$image->brightness($param[0]);
						break;

					case 'invert':
						$image->invert();
						break;

					case 'mask':
						$image->mask(BASE_URI.$param[0], count($param)>1?$param[1]:false);
						break;

					case 'gamma':
						$image->gamma($param[0]);
						break;

					case 'rotate':
						$image->rotate($param[0]);
						break;

					case 'text':
						$image->text($param[0], count($param)>1?$param[1]:0, count($param)>2?$param[2]:0, function($font) use($param) {

							$params = count($param)>3?$param[3]:[];

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

					case 'rectangle':
						$image->rectangle($param[0], $param[1], $param[2], $param[3], function ($draw) use($param) {

							if( count($param) > 4 )
								$draw->background($param[4]);

							if( count($param) > 6 )
								$draw->border($param[5], $param[6]);
						});
						break;

					case 'circle':
						$image->circle($param[0], $param[1], $param[2], function ($draw) use($param) {

							if( count($param) > 3 )
								$draw->background($param[3]);

							if( count($param) > 5 )
								$draw->border($param[4], $param[5]);
						});
						break;

					case 'limitColors':
						$image->limitColors($param[0], count($param)>1?$param[1]:null);
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
     * @param bool $alt
     * @param string $loading
     * @return string
     */
	public function toHTML($w, $h=0, $sources=false, $alt=false, $loading='lazy'){

		if( empty($this->src) || !file_exists($this->src) ){

			$html = '<picture>';
			if( $sources && is_array($sources) ){

				foreach ($sources as $media=>$size)
					$html .='<source media="('.$media.')" srcset="'.$this->placeholder($size[0], count($size)>1?$size[1]:0).'" type="image/jpeg"/>';
			}

			$html .= '<img src="'.$this->placeholder($w, $h).'" alt="'.$this->alt.'" loading="'.$loading.'" '.($w?'width="'.$w.'"':'').' '.($h?'height="'.$h.'"':'').'/>';
			$html .='</picture>';

			return $html;
		}

		$ext = function_exists('imagewebp') ? 'webp' : null;
		$mime = function_exists('imagewebp') ? 'image/webp' : $this->mime_type;

		$html = '<picture>';

		if($this->mime_type == 'image/svg+xml' || $this->mime_type == 'image/svg' || $this->mime_type == 'image/gif' ){

			$html .= '<img src="'.$this->edit(['resize'=>[$w, $h]]).'" alt="'.$this->alt.'" loading="'.$loading.'" type="'.$this->mime_type.'" '.($w?'width="'.$w.'"':'').' '.($h?'height="'.$h.'"':'').'/>';
		}
		else{

			if( $sources && is_array($sources) ){

				foreach ($sources as $media=>$size){

                    if( is_int($media) )
                        $media = 'max-width: '.$media.'px';

					if( $ext == 'webp' )
						$html .='<source media="('.$media.')" srcset="'.$this->edit(['resize'=>$size], $ext).'" type="'.$mime.'"/>';

					$html .='<source media="('.$media.')" srcset="'.$this->edit(['resize'=>$size]).'" type="'.$this->mime_type.'"/>';
				}
			}

			if( $ext == 'webp' && ($w || $h) )
				$html .='<source srcset="'.$this->edit(['resize'=>[$w, $h]], $ext).'" type="'.$mime.'"/>';

            if( !$w && !$h )
                $src = $this->file;
            else
                $src = $this->edit(['resize'=>[$w, $h]]);

            $image_info = file_exists(BASE_URI.PUBLIC_DIR.$src)?getimagesize(BASE_URI.PUBLIC_DIR.$src):[0,0];

			$html .= '<img src="'.$src.'" alt="'.($alt?:$this->alt).'" loading="'.$loading.'" '.($image_info[0]?'width="'.$image_info[0].'"':'').' '.($image_info[0]?'height="'.$image_info[1].'"':'').'/>';
		}

		$html .='</picture>';

		return $html;
	}


	/**
	 * @param \Intervention\Image\Image $image
	 * @param $w
	 * @param int $h
	 * @return void
	 */
	protected function crop($image, $w, $h=0){

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

			if( $src_ratio >= 1 && $dest_ratio <= 1)
			{
				$dest_width = $w*$ratio_height;
				$dest_height = $src_height;
			}
			else
			{
				$dest_width = $src_width;
				$dest_height = $h*$ratio_width;
			}

			if( $dest_width > $src_width ){
				$dest_width = $src_width;
				$dest_height = $h*$ratio_width;
			}

			if( $dest_height > $src_height ){
				$dest_height = $src_height;
				$dest_width = $w*$ratio_height;
			}

				list($cropX1, $cropX2) = $this->calculateCrop($src_width, $dest_width, $this->focus_point['x']/100);
				list($cropY1, $cropY2) = $this->calculateCrop($src_height, $dest_height, $this->focus_point['y']/100);

			$image->crop($cropX2 - $cropX1, $cropY2 - $cropY1, $cropX1, $cropY1);
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
