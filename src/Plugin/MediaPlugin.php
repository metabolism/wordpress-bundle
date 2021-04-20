<?php

namespace Metabolism\WordpressBundle\Plugin;


use Dflydev\DotAccessData\Data;
use Intervention\Image\ImageManagerStatic;
use Metabolism\WordpressBundle\Traits\SingletonTrait;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class MediaPlugin {

	use SingletonTrait;

	protected $config, $prevent_recursion;

	private static $cache=[
		'upload_dir'=>[],
		'relative_upload_dir'=>[]
	];

	/**
	 * Quickly upload file
	 * @param string $file
	 * @param array $allowed_type
	 * @param string $path
	 * @param int $max_size
	 * @return array|\WP_Error
	 */
	public static function upload($file='file', $allowed_type = ['image/jpeg', 'image/jpg', 'image/gif', 'image/png'], $path='/user', $max_size=1048576){

		if( !isset($_FILES[$file]) || empty($_FILES[$file]) )
			return new \WP_Error('empty', 'File '.$file.' is empty');

		$file = $_FILES[$file];

		if ($file['error'] !== UPLOAD_ERR_OK)
			return new \WP_Error('error_upload', 'There was an error uploading your file.');

		if ($file['size'] > $max_size)
			return new \WP_Error('file_size', 'The file is too large');

		$mime_type = mime_content_type($file['tmp_name']);

		if( !in_array($mime_type, $allowed_type) )
			return new \WP_Error('file_format', 'Sorry, this file format is not permitted');

		$name = preg_replace("/[^A-Z0-9._-]/i", "_", basename( $file['name']) );

		$target_file = '/'.uniqid().'_'.$name;
		$upload_dir = WP_UPLOADS_DIR.$path;

		if( !is_dir($upload_dir) )
			mkdir($upload_dir, 0755, true);

		if( !is_writable($upload_dir) )
			return new \WP_Error('right', 'Upload directory is not writable.');

		if( move_uploaded_file($file['tmp_name'], $upload_dir.$target_file) )
			return ['filename' => str_replace('..', '', UPLOADS).$path.$target_file, 'original_filename' => basename( $file['name']), 'type' => $mime_type ];
		else
			return new \WP_Error('move', 'There was an error while writing the file.');
	}


	/**
	 * delete attachment reference on other blog
	 * @param $data
	 * @param $attachment_ID
	 * @return mixed
	 */
	public function updateAttachment($data, $attachment_ID )
	{
		if( $this->prevent_recursion || !isset($_REQUEST['action']) || $_REQUEST['action'] != 'image-editor')
			return $data;

		$this->prevent_recursion = true;

		global $wpdb;

		$main_site_id = get_main_network_id();
		$current_site_id = get_current_blog_id();

		$original_attachment_id = $main_site_id == $current_site_id ? $attachment_ID : get_post_meta( $attachment_ID, '_wp_original_attachment_id', true );

		if( !$original_attachment_id )
			return $data;

		foreach ( get_sites() as $site ) {

			if ( (int) $site->blog_id !== $current_site_id ) {

				switch_to_blog( $site->blog_id );

				if( $main_site_id == $site->blog_id )
				{
					wp_update_attachment_metadata($original_attachment_id, $data);
				}
				else
				{
					$results = $wpdb->get_results( "select `post_id` from $wpdb->postmeta where `meta_value` = '$original_attachment_id' AND `meta_key` = '_wp_original_attachment_id'", ARRAY_A );

					if( !empty($results) )
						wp_update_attachment_metadata($results[0]['post_id'], $data);
				}
			}
		}

		switch_to_blog($current_site_id);

		$this->prevent_recursion = false;

		return $data;
	}


	/**
	 * delete attachment reference on other blog
	 * @param $attachment_ID
	 */
	public function deleteAttachment( $attachment_ID )
	{
		if( $this->prevent_recursion )
			return;

		$this->prevent_recursion = true;

		global $wpdb;

		$main_site_id = get_main_network_id();
		$current_site_id = get_current_blog_id();

		$original_attachment_id = $main_site_id == $current_site_id ? $attachment_ID : get_post_meta( $attachment_ID, '_wp_original_attachment_id', true );

		if( !$original_attachment_id )
			return;

		foreach ( get_sites() as $site ) {

			if ( (int) $site->blog_id !== $current_site_id ) {

				switch_to_blog( $site->blog_id );

				if( $main_site_id == $site->blog_id )
				{
					wp_delete_attachment($original_attachment_id);
				}
				else
				{
					$results = $wpdb->get_results( "select `post_id` from $wpdb->postmeta where `meta_value` = '$original_attachment_id' AND `meta_key` = '_wp_original_attachment_id'", ARRAY_A );
					if( !empty($results) )
						wp_delete_attachment($results[0]['post_id']);
				}

			}
		}

		switch_to_blog($current_site_id);

		$this->prevent_recursion = false;
	}


	/**
	 * add attachment to other blog by reference
	 * @param $attachment_ID
	 * @return void
	 */
	public function addAttachment( $attachment_ID )
	{
		if( $this->prevent_recursion )
			return;

		$this->prevent_recursion = true;

		$attachment = get_post( $attachment_ID );
		$current_site_id = get_current_blog_id();
		$main_site_id = get_main_network_id();

		if( !$attachment )
			return $attachment_ID;

		$attr = [
			'post_mime_type' => $attachment->post_mime_type,
			'filename'       => $attachment->guid,
			'post_title'     => $attachment->post_title,
			'post_status'    => $attachment->post_status,
			'post_parent'    => 0,
			'post_content'   => $attachment->post_content,
			'guid'           => $attachment->guid,
			'post_date'      => $attachment->post_date
		];

		$file = get_attached_file( $attachment_ID );
		$attachment_metadata = wp_get_attachment_metadata( $attachment_ID );

		if( !$attachment_metadata )
			$attachment_metadata = wp_generate_attachment_metadata( $attachment_ID, $file );

		if(!isset($attachment_metadata['file']) ){

			$file = get_post_meta( $attachment_ID, '_wp_attached_file', true );
			$attachment_metadata['file'] = _wp_get_attachment_relative_path( $file ) . basename( $file );
		}

		$original_id = false;

		foreach ( get_sites() as $site ) {

			if ( (int) $site->blog_id !== $current_site_id ) {

				switch_to_blog( $site->blog_id );

				// check if post is already synced
				$attachment = get_posts(['post_type'=>'attachment', 'meta_key' => '_wp_original_attachment_id', 'meta_value' => $attachment_ID, 'fields'=>'ids']);

				if( !count($attachment) )
				{
					// check if a post with the same file exist
					$attachment = get_posts(['post_type'=>'attachment','fields'=>'ids',
						'meta_query' => [
							'relation' => 'AND',
							[
								'key'     => '_wp_attached_file',
								'value'   => $attachment_metadata['file']
							],
							[
								'key'     => '_wp_original_attachment_id',
								'compare' => 'NOT EXISTS'
							]
						]
					]);

					if( !count($attachment) )
					{
						$inserted_id = wp_insert_attachment( $attr, $file );
						if ( !is_wp_error($inserted_id) )
						{
							wp_update_attachment_metadata( $inserted_id, $attachment_metadata );

							if( $main_site_id != $site->blog_id )
								update_post_meta( $inserted_id, '_wp_original_attachment_id', $attachment_ID );
							else
								$original_id = $inserted_id;
						}
					}
					else
					{
						if( $main_site_id != $site->blog_id )
							update_post_meta( $attachment[0], '_wp_original_attachment_id', $attachment_ID );
						else
							$original_id = $attachment[0];
					}
				}
				else
				{
					if( $main_site_id != $site->blog_id )
						$original_id = $attachment[0];
				}
			}
		}

		switch_to_blog( $current_site_id );

		if( $main_site_id != $current_site_id && $original_id )
			update_post_meta( $attachment_ID, '_wp_original_attachment_id', $original_id );

		$this->prevent_recursion = false;
	}


	/**
	 * Unset thumbnail image
	 * @param $post_ID
	 * @return void
	 */
	public function editAttachment($post_ID )
	{
		if( $this->prevent_recursion ){
			return;
		}

		$this->prevent_recursion = true;

		global $wpdb;

		$main_site_id = get_main_network_id();
		$current_site_id = get_current_blog_id();

		$original_attachment_id = $main_site_id == $current_site_id ? $post_ID : get_post_meta( $post_ID, '_wp_original_attachment_id', true );

		if( !$original_attachment_id || empty( $_REQUEST['attachments'] ) || empty( $_REQUEST['attachments'][ $post_ID ] ) )
			return;

		$attachment_data = $_REQUEST['attachments'][ $post_ID ];

		foreach ( get_sites() as $site ) {

			$attachement_id = false;
			if ( (int) $site->blog_id !== $current_site_id ) {

				switch_to_blog( $site->blog_id );

				if( $main_site_id == $site->blog_id ) {
					$attachement_id = $original_attachment_id;
				}
				else
				{
					$results = $wpdb->get_results( "select `post_id` from $wpdb->postmeta where `meta_value` = '$original_attachment_id' AND `meta_key` = '_wp_original_attachment_id'", ARRAY_A );

					if( !empty($results) )
						$attachement_id = $results[0]['post_id'];
				}

				if( $attachement_id ){

					foreach ($attachment_data as $key=>$value)
						update_post_meta( $attachement_id, $key, $value );
				}
			}
		}

		switch_to_blog($current_site_id);

		$this->prevent_recursion = false;
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
			echo '<table id="thumbnails" class="form-table"><tbody>';
			echo '<tr>
				<th scope="row">'.__('Generated thumbnails').'</th>
				<td><a class="button button-primary" href="'.get_admin_url().'?clear_all_thumbnails">Remove '.count($thumbnails).' images</a></td>
			</tr>';

			if( $this->config->get('multisite.shared_media') )
				echo '<tr>
				<th scope="row">'.__('Multisite').'</th>
				<td><a class="button button-primary" href="'.get_admin_url().'?syncronize_images">Synchronize images</a></td>
			</tr>';

			echo '</tbody></table>';
		}
	}


	/**
	 * add admin parameters
	 */
	public function adminInit()
	{
		if( !current_user_can('administrator') )
			return;
		
		if( isset($_GET['clear_thumbnails']) )
			$this->clearThumbnails();

		if( isset($_GET['clear_all_thumbnails']) )
			$this->clearThumbnails(true);

		if( isset($_GET['syncronize_images']) )
			$this->syncMedia();

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
	 * Get all thumbnails
	 * @param bool $all
	 * @return array
	 */
	private function getThumbnails($all=false)
	{
		$folder = wp_upload_dir();
		$folder = $folder['basedir'];

		if( is_multisite() && get_current_blog_id() != 1 && !$this->config->get('multisite.shared_media') && !$all )
			$folder = $folder. '/sites/' . get_current_blog_id() . '/';

		$file_list = [];

		if( is_dir($folder) )
		{
			$dir = new \RecursiveDirectoryIterator($folder);
			$ite = new \RecursiveIteratorIterator($dir);
			$files = new \RegexIterator($ite, '/(?!.*150x150).*-[0-9]+x[0-9]+(-c-default|-c-center)?(-[a-z0-9]*)?\.[a-z]{3,4}$/', \RegexIterator::GET_MATCH);
			$file_list = [];

			foreach($files as $file) {
				if( file_exists($file[0]) )
					$file_list[] = $file[0];
			}
		}

		return $file_list;
	}


	/**
	 * Remove all thumbnails
	 * @param bool $all
	 */
	private function clearThumbnails($all=false)
	{
		if ( current_user_can('administrator') && (!$all || is_super_admin()) )
		{
			$thumbnails = $this->getThumbnails($all);

			foreach($thumbnails as $file){
				if( file_exists($file) )
					unlink($file);
			}
		}

		clearstatcache();

		wp_redirect( get_admin_url(null, $all?'network/settings.php':'options-media.php') );
		exit;
	}


	/**
	 * Synchronize media across multisite instance
	 */
	private function syncMedia()
	{
		if ( current_user_can('administrator') && is_super_admin() )
		{
			set_time_limit(0);
			
			$main_site_id = get_main_network_id();
			$current_site_id = get_current_blog_id();

			global $wpdb;

			switch_to_blog( $main_site_id );
			$results = $wpdb->delete( $wpdb->postmeta, ['meta_key' => '_wp_original_attachment_id']);
			restore_current_blog();

			$network_site_url = trim(network_site_url(), '/');

			foreach ( get_sites() as $site ) {

				switch_to_blog( $site->blog_id );

				//clean guid
				$home_url = get_home_url();
				$wpdb->query("UPDATE $wpdb->posts SET `guid` = REPLACE(guid, '$network_site_url$home_url', '$network_site_url') WHERE `guid` LIKE '$network_site_url$home_url%'");
				$wpdb->query("UPDATE $wpdb->posts SET `guid` = REPLACE(guid, '$home_url', '$network_site_url') WHERE `guid` LIKE '$home_url%' and `post_type`='attachment'");

				$original_attachment_ids = get_posts(['post_type'=>'attachment', 'meta_key' => '_wp_original_attachment_id', 'meta_compare' => 'NOT EXISTS', 'posts_per_page' => -1, 'fields'=>'ids']);

				foreach ($original_attachment_ids as $original_attachment_id)
					$this->addAttachment($original_attachment_id);

				//clean duplicated posts
				$wpdb->query("DELETE p1 FROM $wpdb->posts p1 INNER JOIN $wpdb->posts p2 WHERE p1.ID > p2.ID AND p1.post_title = p2.post_title AND p1.`post_type`='attachment' AND p2.`post_type`='attachment'");
				$wpdb->query("DELETE pm FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL");
			}

			switch_to_blog($current_site_id);
		}

		wp_redirect( get_admin_url(null, 'network/settings.php') );
		exit;
	}


	/**
	 * Redefine upload dir
	 * @param $dirs
	 * @return mixed
	 */
	public function uploadDir($dirs)
	{
		$key = $dirs['subdir'];

		if( isset(self::$cache['upload_dir'][$key]) )
			return self::$cache['upload_dir'][$key];

		$dirs['baseurl'] = str_replace($dirs['relative'],'/uploads', $dirs['baseurl']);

		$paths = explode('/', str_replace($dirs['relative'],'/uploads', $dirs['basedir']));
		foreach ($paths as $key=>$path){
			if( $path == '..'){
				unset($paths[$key-1]);
				unset($paths[$key]);
			}
		}
		$dirs['basedir'] = implode('/', $paths);

		$dirs['url']  = str_replace($dirs['relative'],'/uploads', $dirs['url']);

		$paths = explode('/', str_replace($dirs['relative'],'/uploads', $dirs['path']));
		foreach ($paths as $key=>$path){
			if( $path == '..'){
				unset($paths[$key-1]);
				unset($paths[$key]);
			}
		}
		$dirs['path'] = implode('/', $paths);

		$dirs['relative'] = '/uploads';

		self::$cache['upload_dir'][$key] = $dirs;

		return $dirs;
	}


	/**
	 * Redefine attachment url
	 * @param $url
	 * @return string
	 */
	public function attachmentUrl($url)
	{
		$wp_upload_dir = wp_upload_dir();

		return $wp_upload_dir['relative'].str_replace($wp_upload_dir['baseurl'], '', $url);
	}


	/**
	 * Add relative key
	 * @param $arr
	 * @return mixed
	 */
	public static function add_relative_upload_dir_key( $arr )
	{
		if (DIRECTORY_SEPARATOR === '\\') {

			$arr['path'] = str_replace('\\', '/', $arr['path']);
			$arr['basedir'] = str_replace('\\', '/', $arr['basedir']);
		}

		$key = $arr['subdir'];

		if( isset(self::$cache['relative_upload_dir'][$key]) )
			return self::$cache['relative_upload_dir'][$key];

		$paths = explode('/', $arr['path']);
		foreach ($paths as $key=>$path){
			if( $path == '..'){
				unset($paths[$key-1]);
				unset($paths[$key]);
			}
		}
		$arr['path'] = implode('/', $paths);

		$paths = explode('/', $arr['basedir']);
		foreach ($paths as $key=>$path){
			if( $path == '..'){
				unset($paths[$key-1]);
				unset($paths[$key]);
			}
		}
		$arr['basedir'] = implode('/', $paths);

		$arr['url'] = str_replace('edition/../', '', $arr['url']);
		$arr['baseurl'] = str_replace('edition/../', '', $arr['baseurl']);
		
		$arr['relative'] = str_replace(get_home_url(null,'','http'), '', $arr['baseurl']);
		$arr['relative'] = str_replace(get_home_url(null,'','https'), '', $arr['relative']);

		self::$cache['relative_upload_dir'][$key] = $arr;

		return $arr;
	}


	/**
	 * Resize image on upload to ensure max size
	 * @param $image_data
	 * @return mixed
	 */
	public function uploadResize( $image_data )
	{
	    if( isset($_POST['name']) ){

	        $info = pathinfo($_POST['name']);
	        $info = explode('_', str_replace('-', '_', ($info['filename']??'')));

	        if(count($info) && in_array($info[count($info)-1], ['hd','cmjk','cmjn']) )
	            return $image_data;
        }

		$valid_types = array('image/png','image/jpeg','image/jpg');

		if(in_array($image_data['type'], $valid_types) && $this->config->get('image.resize') ){

			$src = $image_data['file'];

			try {

				$image = ImageManagerStatic::make($src);

				if( $image->getWidth() > $this->config->get('image.resize.max_width', 1920) ){
					$image->resize($this->config->get('image.resize.max_width', 1920), null, function ($constraint) {
						$constraint->aspectRatio();
					});
				}
				elseif( $image->getHeight() > $this->config->get('image.resize.max_height', 2160) ){
					$image->resize(null, $this->config->get('image.resize.max_height', 2160), function ($constraint) {
						$constraint->aspectRatio();
					});
				}

				$image->save($src, 99);
			}
			catch (\Exception $e){}
		}

		return $image_data;
	}

	/**
	 * Unset thumbnail image
	 * @param $sizes
	 * @return mixed
	 */
	public function intermediateImageSizesAdvanced($sizes)
	{
		unset($sizes['medium'], $sizes['medium_large'], $sizes['large']);
		return $sizes;
	}


	/**
	 * Constructor
	 * @param Data $config
	 */
	public function __construct($config)
	{
		$this->config = $config;

		add_filter('upload_dir', [$this, 'add_relative_upload_dir_key'], 10, 2);
		add_filter('wp_calculate_image_srcset_meta', '__return_null');

		if( $this->config->get('multisite.shared_media') && is_multisite() ){

			add_filter('upload_dir', [$this, 'uploadDir'], 11 );
			add_filter('wp_get_attachment_url', [$this, 'attachmentUrl'], 10, 2 );
		}

		if( is_admin() )
		{
			add_action('admin_init', [$this, 'adminInit'] );
			add_action('wpmu_options', [$this, 'wpmuOptions'] );
			add_action('wp_handle_upload', [$this, 'uploadResize']);
			add_filter('intermediate_image_sizes_advanced', [$this, 'intermediateImageSizesAdvanced'] );

			// Replicate media on network
			if( $this->config->get('multisite.shared_media') && is_multisite() )
			{
				add_action('add_attachment', [$this, 'addAttachment']);
				add_action('delete_attachment', [$this, 'deleteAttachment']);
				add_filter('wp_update_attachment_metadata', [$this, 'updateAttachment'], 10, 2);
				add_filter('wpmu_delete_blog_upload_dir', '__return_false' );
				add_action('edit_attachment', [$this, 'editAttachment'], 10 ,2);
			}
		}
	}
}
