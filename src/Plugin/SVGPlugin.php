<?php

namespace Metabolism\WordpressBundle\Plugin;

use enshrined\svgSanitize\Sanitizer;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class SVGPlugin {

	/**
	 * @param $data
	 * @param $post_id
	 * @return mixed
	 */
	public function metadataErrorFix($data, $post_id ) {

		if ( is_wp_error( $data ) ) {
			$data = wp_generate_attachment_metadata( $post_id, get_attached_file( $post_id ) );
			wp_update_attachment_metadata( $post_id, $data );
		}

		return $data;
	}

	/**
	 * @param $result
	 * @param $path
	 * @return bool
	 */
	public function fileIsDisplayableImage($result , $path )
	{
		return pathinfo( $path , PATHINFO_EXTENSION ) == 'svg' || $result;
	}

	/**
	 * @param $mimes
	 * @return mixed
	 */
	public function uploadMimes($mimes )
	{
		$mimes['svg'] = 'image/svg';
		$mimes['svgz'] = 'image/svg+xml';

		return $mimes;
	}

	/**
	 * @param null $data
	 * @param null $file
	 * @param null $filename
	 * @param null $mimes
	 * @return null
	 */
	public function fixMimeTypeSvg($data = null, $file = null, $filename = null, $mimes = null )
	{
		$ext = isset( $data['ext'] ) ? $data['ext'] : '';
		if ( strlen( $ext ) < 1 ) {
			$exploded = explode( '.', $filename );
			$ext      = strtolower( end( $exploded ) );
		}
		if ( $ext === 'svg' ) {
			$data['type'] = 'image/svg';
			$data['ext']  = 'svg';
		} elseif ( $ext === 'svgz' ) {
			$data['type'] = 'image/svg+xml';
			$data['ext']  = 'svgz';
		}

		return $data;
	}

	/**
	 * @param $html
	 * @param $id
	 * @param $alt
	 * @param $title
	 * @param $align
	 * @param $size
	 * @return mixed
	 */
	public function getImageTag($html, $id, $alt, $title, $align, $size ) {

		$mime = get_post_mime_type( $id );

		if ( 'image/svg+xml' === $mime || 'image/svg' === $mime ) {
			if( is_array( $size ) ) {
				$width = $size[0];
				$height = $size[1];
			} else {
				$width  = get_option( "{$size}_size_w", false );
				$height = get_option( "{$size}_size_h", false );
			}

			if( $height && $width ) {
				$html = str_replace( 'width="1" ', sprintf( 'width="%s" ', $width ), $html );
				$html = str_replace( 'height="1" ', sprintf( 'height="%s" ', $height ), $html );
			} else {
				$html = str_replace( 'width="1" ', '', $html );
				$html = str_replace( 'height="1" ', '', $html );
			}
		}

		return $html;
	}

	/**
	 * @param $file
	 * @return bool
	 */
	protected function sanitize($file ) {

		$dirty = file_get_contents( $file );

		if ( $is_zipped = $this->is_gzipped( $dirty ) ) {
			$dirty = gzdecode( $dirty );

			if ( $dirty === false )
				return false;
		}

		$sanitizer = new Sanitizer();

		$clean = $sanitizer->sanitize( $dirty );

		if ( $clean === false )
			return false;

		if ( $is_zipped )
			$clean = gzencode( $clean );

		file_put_contents( $file, $clean );

		return true;
	}

	/**
	 * @param $contents
	 * @return bool
	 */
	protected function is_gzipped($contents ) {

		if ( function_exists( 'mb_strpos' ) ) {
			return 0 === mb_strpos( $contents, "\x1f" . "\x8b" . "\x08" );
		} else {
			return 0 === strpos( $contents, "\x1f" . "\x8b" . "\x08" );
		}
	}

	/**
	 * @param $file
	 * @return mixed
	 */
	public function sanitizeSVG($file ) {

		if ( $file && isset($file['type']) && ($file['type'] === 'image/svg+xml' || $file['type'] === 'image/svg' )) {
			if ( ! $this->sanitize( $file['tmp_name'] ) ) {
				$file['error'] = __( "Sorry, this file couldn't be sanitized so for security reasons wasn't uploaded", 'wordpress-bundle' );
			}
		}

		return $file;
	}

	/**
	 * @param $image
	 * @param $attachment_id
	 * @param $size
	 * @param $icon
	 * @return mixed
	 */
	public function onePixelFix($image, $attachment_id, $size, $icon ) {
		$mime = get_post_mime_type( $attachment_id );
		if ( $mime === 'image/svg+xml' || $mime === 'image/svg' ) {
			$image['1'] = false;
			$image['2'] = false;
		}

		return $image;
	}

	/**
	 * SVGPlugin constructor.
	 * @param $config
	 */
	public function __construct($config)
	{
		$this->config = $config;

		if( !is_admin() )
			return;

		add_filter( 'wp_get_attachment_image_src', array( $this, 'onePixelFix' ), 10, 4 );
		add_filter( 'wp_handle_upload_prefilter', [$this, 'sanitizeSVG']);
		add_filter( 'upload_mimes', [$this, 'uploadMimes']);
		add_filter( 'file_is_displayable_image' , [$this, 'fileIsDisplayableImage'] , 10 , 2 );
		add_filter( 'wp_get_attachment_metadata', [$this, 'metadataErrorFix'], 10, 2 );
		add_filter( 'wp_check_filetype_and_ext', [$this, 'fixMimeTypeSvg'], 75, 4 );

		add_action( 'get_image_tag', [$this, 'getImageTag'], 10, 6 );
	}
}
