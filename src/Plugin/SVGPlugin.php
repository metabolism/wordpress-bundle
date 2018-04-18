<?php

namespace Metabolism\WordpressBundle\Plugin;

use enshrined\svgSanitize\Sanitizer;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class SVGPlugin {

	public function metadataErrorFix( $data, $post_id ) {

		if ( is_wp_error( $data ) ) {
			$data = wp_generate_attachment_metadata( $post_id, get_attached_file( $post_id ) );
			wp_update_attachment_metadata( $post_id, $data );
		}

		return $data;
	}

	public function fileIsDisplayableImage( $result , $path ) {

		return pathinfo( $path , PATHINFO_EXTENSION ) == 'svg' || $result;
	}

	public function uploadMimes( $mimes ) {

		$mimes['svg'] = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';

		return $mimes;
	}

	public function fixMimeTypeSvg( $data = null, $file = null, $filename = null, $mimes = null ) {
		$ext = isset( $data['ext'] ) ? $data['ext'] : '';
		if ( strlen( $ext ) < 1 ) {
			$exploded = explode( '.', $filename );
			$ext      = strtolower( end( $exploded ) );
		}
		if ( $ext === 'svg' ) {
			$data['type'] = 'image/svg+xml';
			$data['ext']  = 'svg';
		} elseif ( $ext === 'svgz' ) {
			$data['type'] = 'image/svg+xml';
			$data['ext']  = 'svgz';
		}

		return $data;
	}

	public function getImageTag( $html, $id, $alt, $title, $align, $size ) {

		$mime = get_post_mime_type( $id );

		if ( 'image/svg+xml' === $mime ) {
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

	protected function sanitize( $file ) {

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

	protected function is_gzipped( $contents ) {

		if ( function_exists( 'mb_strpos' ) ) {
			return 0 === mb_strpos( $contents, "\x1f" . "\x8b" . "\x08" );
		} else {
			return 0 === strpos( $contents, "\x1f" . "\x8b" . "\x08" );
		}
	}

	public function sanitizeSVG( $file ) {

		if ( $file['type'] === 'image/svg+xml' ) {
			if ( ! $this->sanitize( $file['tmp_name'] ) ) {
				$file['error'] = __( "Sorry, this file couldn't be sanitized so for security reasons wasn't uploaded", 'wordpress-bundle' );
			}
		}

		return $file;
	}

	public function onePixelFix( $image, $attachment_id, $size, $icon ) {
		if ( get_post_mime_type( $attachment_id ) == 'image/svg+xml' ) {
			$image['1'] = false;
			$image['2'] = false;
		}

		return $image;
	}

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
