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
	public $mime_type;
	public $title;
	public $caption;
	public $description;

	protected $extension;
	protected $size;
	protected $link;
	protected $alt;
    protected $src;

    protected $post;

    public function __toString()
    {
        return $this->getLink();
    }

    /**
     * Post constructor.
     *
     * @param null $id
     */
	public function __construct($id=null) {

		$this->get($id);
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
	 * Get file data
	 * @param $id
	 * @return void
	 */
	protected function get($id)
	{
        if( is_numeric($id) ){

            if( $post = get_post($id) ) {

                if( is_wp_error($post) )
                    return;

                $file = get_post_meta($id, '_wp_attached_file', true);
                $filename = $this->uploadDir('basedir').'/'.$file;

                if( !file_exists( $filename) )
                    return;

                $this->ID = $post->ID;
                $this->caption = $post->post_excerpt;
                $this->description = $post->post_content;
                $this->file = $file;
                $this->src = $filename;
                $this->post = $post;
                $this->title = $post->post_title;
                $this->mime_type = $post->post_mime_type;
            }
        }
        else{

            $filename = BASE_URI.PUBLIC_DIR.$id;

            if( !file_exists( $filename) || is_dir( $filename ) )
                return;

            $this->ID = 0;
            $this->file = $id;
            $this->src = $filename;
            $this->post = false;

	        if( isset($this->args['title']) )
		        $this->title = $this->args['title'];
	        else
		        $this->title = str_replace('_', ' ', pathinfo($filename, PATHINFO_FILENAME));

			$this->mime_type = mime_content_type($filename);
        }
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
	 * @return string|null
	 */
	public function getLink(){

		if ($this->ID)
			$this->link = wp_get_attachment_url($this->ID);
		else
			$this->link = home_url($this->file);

		return $this->link;
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
	 * @return mixed
	 */
	public function getSrc(){

		return $this->src;
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
	 * @return false|string
	 */
	public function getFileContent(){

		if( file_exists($this->src) )
			return file_get_contents($this->src);
		else
			return 'File does not exist';
	}
}
