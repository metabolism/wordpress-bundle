<?php

namespace Metabolism\WordpressBundle\Entity;

use Intervention\Image\Geometry\Factories\CircleFactory;
use Intervention\Image\Geometry\Factories\RectangleFactory;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\FontFactory;
use kornrunner\Blurhash\Blurhash;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class Image
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Image extends Entity
{
    public $entity = 'image';

    public static $wp_upload_dir = false;

    protected $caption;
    protected $description;
    protected $file;
    protected $width;
    protected $height;
    protected $mime_type;
    protected $sizes = [];
    protected $title;
    protected $alt;
    protected $link;
    protected $extension;
    protected $size;
    protected $focus_point;
    protected $metadata;
    protected $src;
    protected $post;
    protected $ratio;

    protected $compression;
    protected $args;

    public function __toString(): string
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

        if (!$id || ($_REQUEST['debug']??false == 'image' && WP_DEBUG)) {

            $this->ID = 0;
        }
        else {

            $this->get($id);

            $this->loadMetafields($this->ID, 'post');
        }
    }


    /**
     * @param $field
     * @return mixed|string
     */
    public static function uploadDir($field)
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
        return $this->ID === 0 || ($this->src && file_exists( $this->src ));
    }


    /**
     * @param bool $force
     * @return bool
     */
    public static function invalidateCache($url, $force=false){

        $filename =  self::getCachePath($url);

        if( !$filename || !file_exists($filename) )
            return false;

        $filepath = dirname($filename);

        $filesystem = new Filesystem();

        if( $force ){

            $filesystem->remove($filepath);
            return true;
        }
        else{

            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_exec($ch);

            $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

            curl_close($ch);

            if( $size == -1 || $size != filesize($filename) ){

                $filesystem->remove($filepath);
                return true;
            }
        }

        return false;
    }


    /**
     * @return false|string
     */
    public static function getCachePath($url){

        if( !$url )
            return false;

        $basename = strtolower(pathinfo($url, PATHINFO_BASENAME));
        $folder = '/cache/'.md5($url);

        return self::uploadDir('basedir').$folder.'/'.$basename;
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

        $filename = self::getCachePath($url);

        $folderpath = dirname($filename);
        $relative_filename =  str_replace(self::uploadDir('basedir'), self::uploadDir('relative'), $filename);

        if( is_readable($filename) ){

            $cachetime = filemtime($filename) + $ttl;

            if( $cachetime > time() )
                return $relative_filename;
        }

        $tmpfile = tempnam ('/tmp', 'img-');

        if( !$data = @file_get_contents($url) )
            return false;

        file_put_contents($tmpfile, $data);

        $mime_type = mime_content_type($tmpfile);

        if( !in_array($mime_type, ['image/jpeg', 'image/jpg', 'image/gif', 'image/png']) ){

            unlink($tmpfile);
            return false;
        }

        if( !is_dir($folderpath) )
            mkdir($folderpath, 0755, true);

        if( file_exists($filename) && !is_readable($filename) )
            return false;

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

                if( is_wp_error($post) )
                    return;

                $post_meta = get_post_meta($id);

                $attachment_metadata = apply_filters( 'wp_get_attachment_metadata', maybe_unserialize($post_meta['_wp_attachment_metadata'][0]??''), $id );

                if( !$attachment_metadata || !isset($attachment_metadata['file']) ){

                    if( $post->post_mime_type != 'image/svg' && $post->post_mime_type != 'image/svg+xml' )
                        return;

                    $filename = self::uploadDir('basedir').'/'.$post_meta['_wp_attached_file'][0];

                    if( !$xmlget = @simplexml_load_file($filename) )
                        return;

                    $xmlattributes = $xmlget->attributes();
                    $width = $xmlattributes->width??'';
                    $height = $xmlattributes->height??'';

                    $attachment_metadata = [
                        'file' => $post_meta['_wp_attached_file'][0],
                        'width' =>  $width,
                        'height' =>  $height,
                        'image_meta' =>  []
                    ];

                    $this->focus_point = false;
                }

                $filename = self::uploadDir('basedir').'/'.$attachment_metadata['file'];

                if( !is_readable( $filename) )
                    return;

                $this->ID = $post->ID;
                $this->caption = $post->post_excerpt;
                $this->description = $post->post_content;
                $this->file = self::uploadDir('relative').'/'.$attachment_metadata['file'];
                $this->src = $filename;
                $this->post = $post;

                $this->title = $post->post_title;
                $this->alt = trim(strip_tags($post_meta['_wp_attachment_image_alt'][0]??''));

                $this->width = $attachment_metadata['width'];
                $this->height = $attachment_metadata['height'];
                $this->ratio = $this->width/$this->height;
                $this->metadata = $attachment_metadata['image_meta'];
                $this->mime_type = $post->post_mime_type;
            }
        }
        else{

            if( substr($id,0, 7) == 'http://' || substr($id,0, 8) == 'https://' )
                $id = $this->getRemoteImage($id, $this->args['ttl']??false);

            if( !$id )
                return;

            $filename = BASE_URI.PUBLIC_DIR.$id;

            if( is_dir( $filename ) || !is_readable($filename) )
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
            $this->ratio = $this->width/$this->height;

            $this->metadata = false;
            $this->mime_type = mime_content_type($filename);
        }
    }

    /**
     * @return string|null
     */
    public function getLink(){

        if( is_null($this->link) ){

            if ($this->ID)
                $this->link = wp_get_attachment_url($this->ID);
            else
                $this->link = home_url($this->file);
        }

        return $this->link;
    }

    /**
     * @return string
     */
    public function getCaption(){

        return $this->caption;
    }

    /**
     * @return string
     */
    public function getDescription(){

        return $this->description;
    }

    /**
     * @return string
     */
    public function getFile(){

        return $this->file;
    }

    /**
     * @return string
     */
    public function getWidth(){

        return $this->width;
    }

    /**
     * @return string
     */
    public function getHeight(){

        return $this->height;
    }

    /**
     * @return string
     */
    public function getMimeType(){

        return $this->mime_type;
    }

    /**
     * @return string
     */
    public function getTitle(){

        return $this->title;
    }

    /**
     * @return string
     */
    public function getAlt(){

        return $this->alt;
    }

    /**
     * @return string
     */
    public function getSizes(){

        return $this->sizes;
    }

    /**
     * @deprecated
     * @return string|null
     */
    public function getUrl(){

        return $this->getLink();
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
     * @deprecated
     * @return float|int
     */
    public function getSize(){

        return $this->getFilesize();
    }

    /**
     * @return float|int
     */
    public function getFilesize(){

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
     * @param bool|string $format
     * @return mixed|null
     */
    public function getDate($format=true){

        if( $this->post )
            return $this->formatDate($this->post->post_date, $format);
        else
            return $this->formatDate(filemtime($this->src), $format);
    }

    /**
     * @param bool|string $format
     * @return mixed|null
     */
    public function getModified($format=true){

        if( $this->post )
            return $this->formatDate($this->post->post_modified, $format);
        else
            return $this->formatDate(filectime($this->src), $format);
    }

    /**
     * @param bool|string $format
     * @return mixed|null
     */
    public function getDateGmt($format=true){

        if( $this->post )
            return $this->formatDate($this->post->post_date_gmt, $format);
        else
            return $this->formatDate(filemtime($this->src), $format);
    }

    /**
     * @param bool|string $format
     * @return mixed|null
     */
    public function getModifiedGmt($format=true){

        if( $this->post )
            return $this->formatDate($this->post->post_modified_gmt, $format);
        else
            return $this->formatDate(filectime($this->src), $format);
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
     * @return mixed
     */
    public function getBlurhash(){

        if( $this->mime_type == 'image/svg' || $this->mime_type == 'image/svg+xml' )
            return false;

        if( !($this->metadata['blurhash']??false) ){

            $components_x = 4;
            $components_y = 3;

            $manager = ImageManager::gd();
            $image = $manager->read($this->src);
            $src_ratio = $this->width/$this->height;

            $image = $image->cover(
                $this->width >= $this->height ? 64 : 64*$src_ratio,
                $this->width >= $this->height ? 64/$src_ratio : 64
            );

            $height = $image->height();
            $width = $image->width();

            $pixels = [];

            for ($y = 0; $y < $height; ++$y) {
                $row = [];
                for ($x = 0; $x < $width; ++$x) {
                    $colors = $image->pickColor($x, $y);

                    $row[] = $colors->toArray();
                }
                $pixels[] = $row;
            }

            $this->metadata['blurhash'] = Blurhash::encode($pixels, $components_x, $components_y);

            $attachment_metadata = maybe_unserialize(get_post_meta(  $this->ID, '_wp_attachment_metadata', true ));
            $attachment_metadata['blurhash'] = $this->metadata['blurhash'];

            update_post_meta($this->ID, '_wp_attachment_metadata', $attachment_metadata);
        }

        return $this->metadata['blurhash'];
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

        if( !$w && !$h )
            $file = ['src'=>$this->src, 'url'=>$this->file];
        else
            $file = $this->edit($params, $ext, 'object');

        $image_info = is_readable($file['src'])?getimagesize($file['src']):[0,0, 'mime'=>''];

        if( $name ){

            unset($params['resize']);

            $this->sizes[$name][] = array_merge([
                'file'=>$file['url'],
                'extension'=>$ext,
                'mime-type'=>$image_info['mime'],
                'width'=>$image_info[0],
                'height'=>$image_info[1]
            ], $params);
        }

        return $file['url'];
    }


    /**
     * @param array $params
     * @param null $ext
     * @return string|array
     */
    public function edit($params, $ext=null, $output='url'){

        $file = $this->process($params, $ext);

        $file['url'] = str_replace(self::uploadDir('basedir'), self::uploadDir('baseurl'), $file['src']);
        $file['url'] = str_replace(BASE_URI.PUBLIC_DIR, '', $file['url']);

        if( $output == 'url')
            return $file['url'];
        else
            return $file;
    }


    /**
     * Edit image using Intervention library
     * @param array $params
     * @param null $ext
     * @return array
     */
    private function process($params, $ext=null){

        if( $this->src && !in_array($this->getExtension(), ['jpg','jpeg','png','gif','webp']) )
            return ['src'=>$this->src];

        $this->getFocusPoint();

        //redefine ext if webp is not supported
        if( $ext === 'webp' && !function_exists('imagewebp'))
            $ext = null;

        //get size from params
        if( isset($params['resize']) ){

            $params['resize'] = (array)$params['resize'];
            $w = $params['resize'][0];
            $h = count($params['resize'])>1?$params['resize'][1]:0;

            if( !($params['enlarge']??true) ){

                $w = min($this->width, $w);
                $h = min($this->height, $h);
            }
        }
        else{

            if( is_readable($this->src) ){
                $w = $this->width;
                $h = $this->height;
            }
            else{
                $w = 800;
                $h = 600;
            }
        }

        if( isset($params['gcd']) ){

            if( ($w == 0 || $h == 0) && is_readable($this->src) ){

                if( $w == 0 )
                    $w = round($h*$this->ratio);
                else
                    $h = round($w/$this->ratio);
            }

            $w = round($w/10);
            $h = round($h/10);
        }


        //return placeholder if image is empty
        if( empty($this->src) || !is_readable($this->src) )
            return ['src'=>$this->placeholder($w, $h), 'width'=>$w, 'height'=>$h];

        //remove focus point if invalid
        if( !is_array($this->focus_point) || !isset($this->focus_point['x'], $this->focus_point['y']) )
            $this->focus_point = false;

        //return if image is svg or gif
        if( $this->mime_type == 'image/svg+xml' || $this->mime_type == 'image/svg' || $this->mime_type == 'image/gif' )
            return ['src'=>$this->src];

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

        $w = round($w);
        $h = round($h);

        $suffix = '-'.$w.'x'.$h;

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

        if( is_readable($dest) ){

            if( filemtime($dest) > filemtime($this->src) )
                return ['src'=>$dest, 'width'=>$w, 'height'=>$h];
            else
                unlink($dest);
        }

        $manager = ImageManager::gd();
        $image = $manager->read($this->src);

        foreach ($params as $type=>$param){

            $param = (array)$param;

            switch ($type){

                case 'resize':
                case 'crop':
                $this->crop($image, $w, $h);
                break;

                case 'insert':
                case 'place':
                $image->place(BASE_URI.$param[0], count($param)>1?$param[1]:'top-left', $param[2]??0, $param[3]??0, $param[4]??100);
                break;

                case 'colorize':
                    $image->colorize($param[0]??0, $param[1]??0, $param[2]??0);
                    break;

                case 'blur':
                    $image->blur($param[0]??5);
                    break;

                case 'flip':
                    if( $param[0]??'v')
                        $image->flop();
                    else
                        $image->flip();
                    break;

                case 'flop':
                    $image->flop();
                    break;

                case 'brightness':
                    $image->brightness($param[0]);
                    break;

                case 'invert':
                    $image->invert();
                    break;

                case 'gamma':
                    $image->gamma($param[0]);
                    break;

                case 'rotate':
                    $image->rotate($param[0], $param[1]??'ffffff');
                    break;

                case 'text':
                    $image->text($param[0], $param[1]??0, $param[2]??0, function(FontFactory $font) use($param) {

                        $params = $param[3]??[];

                        if( isset($params['file']) )
                            $font->filename(BASE_URI.$params['file']);

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

                        if( isset($params['lineHeight']) )
                            $font->lineHeight($params['lineHeight']);

                        if( isset($params['wrap']) )
                            $font->wrap($params['wrap']);
                    });

                    break;

                case 'pixelate':
                    $image->pixelate($param[0]);
                    break;

                case 'greyscale':
                    $image->greyscale();
                    break;

                case 'rectangle':
                case 'drawRectangle':
                $image->drawRectangle($param[0], $param[1], function (RectangleFactory $draw) use($param) {

                    if( count($param) > 3 )
                        $draw->size($param[2], $param[3]);

                    if( count($param) > 4 )
                        $draw->background($param[4]);

                    if( count($param) > 6 )
                        $draw->border($param[5], $param[6]);
                });
                break;

                case 'circle':
                case 'drawCircle':
                $image->drawCircle($param[0], $param[1], function (CircleFactory $draw) use($param) {

                    if( count($param) > 2 )
                        $draw->radius($param[2]);

                    if( count($param) > 3 )
                        $draw->background($param[3]);

                    if( count($param) > 5 )
                        $draw->border($param[4], $param[5]);
                });
                break;

                case 'limitColors':
                case 'reduceColors':
                $image->reduceColors($param[0], $param[1]??'transparent');
                break;
            }
        }

        $image->save($dest, $this->compression);

        return ['src'=>$dest, 'width'=>$w, 'height'=>$h];
    }


    /**
     * @param int $w
     * @param int $h
     * @return string
     */
    public function placeholder($w, $h=0){

        $width = $w == 0 ? 1280 : $w;
        $height = $h > 0 ? 'x'.$h : '';

        return 'https://placehold.jp/'.$width.$height.'.png';
    }

    /**
     * @deprecated
     * Use picture
     */
    public function toHTML($w, $h=0, $sources=false, $alt=false, $loading='lazy'){

        return $this->picture($w, $h, $sources, $alt, $loading);
    }

    /**
     * @param $w
     * @param int $h
     * @param bool $sources
     * @param bool $alt
     * @param string $loading
     * @param array $params
     * @return string
     */
    public function picture($w, $h=0, $sources=false, $alt=false, $loading='lazy', $params=[]){

        $alt = $alt?:$this->alt;
        $alt = htmlspecialchars($alt?:'', ENT_QUOTES, 'UTF-8');

        if( empty($this->src) || !is_readable($this->src) ){

            $html = '<picture>';
            if( $sources && is_array($sources) ){

                foreach ($sources as $media=>$size)
                    $html .='<source media="('.$media.')" srcset="'.$this->placeholder($size[0], $size[1]??0).'" type="image/jpeg"/>';
            }

            $html .= '<img loading="'.$loading.'" src="'.$this->placeholder($w, $h).'" alt="'.$alt.'" '.($w?'width="'.$w.'"':'').' '.($h?'height="'.$h.'"':'').'/>';
            $html .='</picture>';

            return $html;
        }

        $ext = function_exists('imagewebp') ? 'webp' : null;
        $mime = function_exists('imagewebp') ? 'image/webp' : $this->mime_type;

        $html = '<picture>';

        if($this->mime_type == 'image/svg+xml' || $this->mime_type == 'image/svg' || $this->mime_type == 'image/gif' ){

            $html .= '<img loading="'.$loading.'" src="'.$this->edit(['resize'=>[$w, $h]]).'" alt="'.$alt.'" '.($w?'width="'.$w.'"':'').' '.($h?'height="'.$h.'"':'').'/>';
        }
        else{

            if( $sources && is_array($sources) ){

                foreach ($sources as $media=>$size){

                    $params['resize'] = $size;

                    if( is_int($media) )
                        $media = 'max-width: '.$media.'px';

                    if( $ext == 'webp' )
                        $html .='<source media="('.$media.')" srcset="'.$this->edit($params, $ext).'" type="'.$mime.'"/>';

                    $html .='<source media="('.$media.')" srcset="'.$this->edit($params).'" type="'.$this->mime_type.'"/>';
                }
            }

            $params['resize'] = [$w, $h];

            if( $ext == 'webp' && ($w || $h) )
                $html .='<source srcset="'.$this->edit($params, $ext).'" type="'.$mime.'"/>';

            if( !$w && !$h )
                $file = ['src'=>$this->src, 'url'=>$this->file];
            else
                $file = $this->edit($params, null, 'object');

            $image_info = getimagesize($file['src']);

            $html .= '<img loading="'.$loading.'" src="'.$file['url'].'" alt="'.$alt.'" '.($image_info[0]?'width="'.$image_info[0].'"':'').' '.($image_info[1]?'height="'.$image_info[1].'"':'').'/>';
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

        $src_width = $image->width();
        $src_height = $image->height();
        $src_ratio = $src_width/$src_height;

        if($this->focus_point && $w && $h){

            $dest_ratio = $w/$h;
            $ratio_height = $src_height/$h;
            $ratio_width = $src_width/$w;

            if( $src_ratio >= 1 && $dest_ratio <= 1) {

                $dest_width = $w*$ratio_height;
                $dest_height = $src_height;
            }
            else {

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
        }

        if( !$w )
            $w = $h*$src_ratio;

        if( !$h )
            $h = $w/$src_ratio;

        $image->cover($w, $h);
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
        }
        else if ($cropEnd > $origSize) {

            $cropStart -= ($cropEnd - $origSize);
            $cropEnd = $origSize;
        }

        return array(ceil($cropStart), ceil($cropEnd));
    }
}
