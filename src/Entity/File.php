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
	public $mime_type;
	public $extension;
	public $title;
	public $caption;
	public $description;
	public $size;

    protected $alt;
    protected $date;
    protected $date_gmt;
    protected $modified;
    protected $modified_gmt;
    protected $src;

    private $post;
    private $args = [];

    public function __toString()
    {
        return $this->link;
    }

	/**
	 * Post constructor.
	 *
	 * @param int|string $id
	 * @param array $args
	 */
	public function __construct($id=null, $args=[]) {

		$this->args = $args;

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

            $filename = BASE_URI.$id;

            if( !file_exists( $filename) || is_dir( $filename ) )
                return;

            $this->ID = 0;
            $this->file = $id;
            $this->src = $filename;
            $this->post = false;
            $this->title = str_replace('_', ' ', pathinfo($filename, PATHINFO_FILENAME));
            $this->mime_type = mime_content_type($filename);
        }

        if( $this->src ){

            $this->link = home_url($this->file);
            $this->extension = pathinfo($this->src, PATHINFO_EXTENSION);
            $this->size = filesize($this->src);
        }
    }


	public function getSrc(){

		return $this->src;
	}

    public function getDate(){

        if( is_null($this->date) ){

            if( $this->post )
                $this->date = $this->formatDate($this->post->post_date);
            else
                $this->date = $this->formatDate(filemtime($this->src));
        }

        return $this->date;
    }

    public function getModified(){

        if( is_null($this->modified) ){

            if( $this->post )
                $this->modified = $this->formatDate($this->post->post_modified);
            else
                $this->modified = $this->formatDate(filectime($this->src));
        }

        return $this->modified;
    }

    public function getDateGmt(){

        if( is_null($this->date_gmt) ){

            if( $this->post )
                $this->date_gmt = $this->formatDate($this->post->post_date_gmt);
            else
                $this->date_gmt = $this->formatDate(filemtime($this->src));
        }

        return $this->date_gmt;
    }

    public function getModifiedGmt(){

        if( is_null($this->modified_gmt) ){

            if( $this->post )
                $this->modified_gmt = $this->formatDate($this->post->post_modified_gmt);
            else
                $this->modified_gmt = $this->formatDate(filectime($this->src));
        }

        return $this->modified_gmt;
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
