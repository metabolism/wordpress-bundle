<?php

namespace Metabolism\WordpressLoader\Plugin;


/**
 * Class Metabolism\WordpressLoader Framework
 */
class MediaPlugin {

	protected $config;

	/**
	 * Quickly upload file
	 */
	public static function upload($file='file', $allowed_type = ['image/jpeg', 'image/gif', 'image/png'], $path='/user', $max_size=1048576){

		if( !isset($_FILES[$file]) or empty($_FILES[$file]) )
			return false;

		$file = $_FILES[$file];

		if ($file['error'] !== UPLOAD_ERR_OK)
			return ['error' => true, 'message' => 'Sorry, there was an error uploading your file.' ];

		if ($file['size'] > $max_size)
			return ['error' => true, 'message' => 'Sorry, the file is too large.' ];

		$mime_type = mime_content_type($file['tmp_name']);

		if( !in_array($mime_type, $allowed_type) )
			return ['error' => true, 'message' => 'Sorry, this file format is not permitted' ];

		$name = preg_replace("/[^A-Z0-9._-]/i", "_", basename( $file['name']) );

		$target_file = '/uploads'.$path.'/'.uniqid().'_'.$name;
		$upload_dir = WP_CONTENT_DIR.'/uploads'.$path;

		if( !is_dir($upload_dir) )
			mkdir($upload_dir, 0777, true);

		if( !is_writable($upload_dir) )
			return ['error' => true, 'message' => 'Sorry, upload directory is not writable.' ];

		if( move_uploaded_file($file['tmp_name'], WP_CONTENT_DIR.$target_file) )
			return ['filename' => $target_file, 'original_filename' => basename( $file['name']), 'type' => $mime_type ];
		else
			return ['error' => true, 'message' => 'Sorry, there was an error uploading your file.' ];
	}


	/**
	 * delete attachment reference on other blog
	 */
	public function updateAttachment($data, $attachment_ID )
	{
		if( $this->prevent_recurssion || !isset($_REQUEST['action']) || $_REQUEST['action'] != 'image-editor')
			return $data;

		$this->prevent_recurssion = true;

		global $wpdb;

		$current_site_id = get_current_blog_id();
		$original_attachment_id = get_post_meta( $attachment_ID, '_wp_original_attachment_id', true );

		foreach ( get_sites() as $site ) {

			if ( (int) $site->blog_id !== $current_site_id ) {

				switch_to_blog( $site->blog_id );

				if( $original_attachment_id )
				{
					$results = $wpdb->get_results( "select `post_id` from $wpdb->postmeta where `meta_value` = '$original_attachment_id' AND `meta_key` = '_wp_original_attachment_id'", ARRAY_A );

					if( !empty($results) )
						wp_update_attachment_metadata($results[0]['post_id'], $data);
				}
				else
				{
					wp_update_attachment_metadata($attachment_ID, $data);
				}
			}
		}

		restore_current_blog();

		$this->prevent_recurssion = false;

		return $data;
	}


	/**
	 * delete attachment reference on other blog
	 */
	public function deleteAttachment( $attachment_ID )
	{
		if( $this->prevent_recurssion )
			return;

		$this->prevent_recurssion = true;

		global $wpdb;

		$current_site_id = get_current_blog_id();
		$original_attachment_id = get_post_meta( $attachment_ID, '_wp_original_attachment_id', true );

		foreach ( get_sites() as $site ) {

			if ( (int) $site->blog_id !== $current_site_id ) {

				switch_to_blog( $site->blog_id );

				if( $original_attachment_id )
				{
					$results = $wpdb->get_results( "select `post_id` from $wpdb->postmeta where `meta_value` = '$original_attachment_id' AND `meta_key` = '_wp_original_attachment_id'", ARRAY_A );

					if( !empty($results) )
						wp_delete_attachment($results[0]['post_id']);
				}
				else
				{
					wp_delete_attachment($attachment_ID);
				}
			}
		}

		restore_current_blog();

		$this->prevent_recurssion = false;
	}


	/**
	 * add attachment to other blog by reference
	 */
	public function addAttachment( $attachment_ID )
	{
		if( $this->prevent_recurssion )
			return;

		$this->prevent_recurssion = true;

		$attachment = get_post( $attachment_ID );
		$current_site_id = get_current_blog_id();

		$attr = [
			'post_mime_type' => $attachment->post_mime_type,
			'filename'       => $attachment->guid,
			'post_title'     => $attachment->post_title,
			'post_status'    => $attachment->post_status,
			'post_parent'    => 0,
			'post_content'   => $attachment->post_content,
			'guid'           => $attachment->guid
		];

		$file = get_attached_file( $attachment_ID );
		$attachment_metadata = wp_generate_attachment_metadata( $attachment_ID, $file );

		add_post_meta( $attachment_ID, '_wp_original_attachment_id', $attachment_ID );

		foreach ( get_sites() as $site ) {

			if ( (int) $site->blog_id !== $current_site_id ) {

				switch_to_blog( $site->blog_id );

				$inserted_id = wp_insert_attachment( $attr, $file );
				if ( !is_wp_error($inserted_id) )
				{
					wp_update_attachment_metadata( $inserted_id, $attachment_metadata );
					add_post_meta( $inserted_id, '_wp_original_attachment_id', $attachment_ID );
				}

			}
		}

		restore_current_blog();

		$this->prevent_recurssion = false;
	}


	/**
	 * add network parameters
	 */
	public function wpmuOptions()
	{
		// Remove generated thumbnails option
		$thumbnails = $this->getThumbnails(true);

		if( count($thumbnails) )
		{
			echo '<h2>Images</h2>';
			echo '<table id="thumbnails" class="form-table">
			<tbody><tr>
				<th scope="row">'.__('Generated thumbnails').'</th>
				<td><a class="button button-primary" href="'.get_admin_url().'?clear_all_thumbnails">Remove '.count($thumbnails).' images</a></td>
			</tr>
		</tbody></table>';
		}
	}


	/**
	 * add admin parameters
	 */
	public function adminInit()
	{
		if( isset($_GET['clear_thumbnails']) )
			$this->clearThumbnails();

		if( isset($_GET['clear_all_thumbnails']) )
			$this->clearThumbnails(true);

		// Remove generated thumbnails option
		add_settings_field('clean_image_thumbnails', __('Generated thumbnails'), function(){

			$thumbnails = $this->getThumbnails();

			if( count($thumbnails) )
				echo '<a class="button button-primary" href="'.get_admin_url().'?clear_thumbnails">'.__('Remove').' '.count($thumbnails).' images</a>';
			else
				echo __('Nothing to remove');

		}, 'media');

	}


	/**
	 * Remove all thumbnails
	 */
	private function getThumbnails($all=false)
	{
		$folder = BASE_URI. '/src/WordpressBundle/uploads/';

		if( is_multisite() && get_current_blog_id() != 1 && !$this->config->get('multisite.shared_media') && !$all )
			$folder = BASE_URI. '/src/WordpressBundle/uploads/sites/' . get_current_blog_id() . '/';

		$file_list = [];

		if( is_dir($folder) )
		{
			$dir = new \RecursiveDirectoryIterator($folder);
			$ite = new \RecursiveIteratorIterator($dir);
			$files = new \RegexIterator($ite, '/(?!.*150x150).*-[0-9]+x[0-9]+(-c-default|-c-center)?\.[a-z]{3,4}$/', \RegexIterator::GET_MATCH);
			$file_list = [];

			foreach($files as $file)
				$file_list[] = $file[0];
		}

		return $file_list;
	}


	/**
	 * Remove all thumbnails
	 */
	private function clearThumbnails($all=false)
	{
		if ( current_user_can('administrator') && (!$all || is_super_admin()) )
		{
			$thumbnails = $this->getThumbnails($all);

			foreach($thumbnails as $file)
				unlink($file);
		}

		clearstatcache();

		wp_redirect( get_admin_url(null, $all?'network/settings.php':'options-media.php') );
	}

	
	/**
	 * Redefine upload dir
	 * @see Menu
	 */
	public function uploadDir($dirs)
	{
		$dirs['baseurl'] = str_replace($dirs['relative'],'/uploads', $dirs['baseurl']);
		$dirs['basedir'] = str_replace($dirs['relative'],'/uploads', $dirs['basedir']);

		$dirs['url']  = str_replace($dirs['relative'],'/uploads', $dirs['url']);
		$dirs['path'] = str_replace($dirs['relative'],'/uploads', $dirs['path']);

		$dirs['relative'] = '/uploads';

		return $dirs;
	}

	
	public function __construct($config)
	{
		$this->config = $config;

		if( $this->config->get('multisite.shared_media') and is_multisite() )
			add_filter( 'upload_dir', [$this, 'uploadDir'], 11 );

		if( is_admin() )
		{
			add_action( 'init', function()
			{
				add_action( 'admin_init', [$this, 'adminInit'] );
				add_action( 'wpmu_options', [$this, 'wpmuOptions'] );

				// Replicate media on network
				if( $this->config->get('multisite.shared_media') and is_multisite() )
				{
					add_action( 'add_attachment', [$this, 'addAttachment']);
					add_action( 'delete_attachment', [$this, 'deleteAttachment']);
					add_filter( 'wp_update_attachment_metadata', [$this, 'updateAttachment'], 10, 2);
					add_filter( 'wpmu_delete_blog_upload_dir', '__return_false' );
				}
			});
		}
	}
}
