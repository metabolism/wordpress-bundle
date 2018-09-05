<?php

namespace Metabolism\WordpressBundle\Plugin;

use Ifsnop\Mysqldump as IMysqldump;
use Metabolism\WordpressBundle\Helper\DirFilterHelper;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class BackupPlugin {

	protected $config;

	private function dumpFolder($zip, $source, $exclude = [], $exclude_pattern=false)
	{
		if ( is_string( $source ) )
			$source_arr = [$source];
		else
			$source_arr = $source;

		foreach ( $source_arr as $source ) {

			$source = str_replace( '\\', '/', realpath( $source ) );
			$folder = "";

			if ( count( $source_arr ) > 1 ) {

				$folder = substr( $source, strrpos( $source, '/' ) + 1 ) . '/';
				$zip->addEmptyDir( $folder );
			}

			if ( is_dir( $source ) === true ) {

				$directory = new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS);
				$filtered = new DirFilterHelper($directory, $exclude);
				$iterator = new \RecursiveIteratorIterator($filtered, \RecursiveIteratorIterator::SELF_FIRST);

				foreach ( $iterator as $file ) {

					$file = str_replace( '\\', '/', $file );

					if( $exclude_pattern && preg_match($exclude_pattern, $file))
						continue;

					$file = realpath( $file );

					if ( is_dir( $file ) === true ) {

						$zip->addEmptyDir( $folder . str_replace( $source . '/', '', $file . '/' ) );
					}
					else {

						if ( is_file( $file ) === true ) {

							$localname = $folder . str_replace( $source . '/', '', $file );
							$zip->addFile($file, $localname);
							$zip->setCompressionName($localname, \ZipArchive::CM_STORE);
						}
					}
				}
			}
			else {

				if ( is_file( $source ) === true ) {

					$localname = $folder . basename( $source );
					$zip->addFile($source, $localname);
					$zip->setCompressionName($localname, \ZipArchive::CM_STORE);
				}
			}
		}
	}


	/**
	 * Remove all thumbnails
	 */
	private function dumpDatabase($zip, $path)
	{
		try {
			$localname = 'db.sql';
			$file = $path.'/'.$localname;

			if( file_exists($file) )
				unlink($file);

			$dump = new IMysqldump\Mysqldump('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD, ['add-drop-table' => true]);
			$dump->start($file);

			if( file_exists($file) ){

				$zip->addFile($file, $localname);
				$zip->setCompressionName($localname, \ZipArchive::CM_DEFAULT);
			}

			return true;
		}
		catch (\Exception $e)
		{
			return new \WP_Error('mysqldump-error', $e->getMessage());
		}
	}


	/**
	 * Remove all thumbnails
	 */
	private function init($destination){

		if ( !extension_loaded( 'zip' ) )
			return new \WP_Error('zip_extension', 'Zip Extension is not loaded');

		$zip = new \ZipArchive();

		if ( !$zip->open( $destination, \ZipArchive::CREATE ) )
			return new \WP_Error('archive', 'Can\'t create archive file');

		return $zip;
	}


	/**
	 * Init zip file
	 */
	private function create($global=false, $type='all', $filename)
	{
		$backup = false;

		if ( current_user_can('administrator') && (!$global || is_super_admin()) )
		{
			$folder = wp_upload_dir();
			$rootPath = $folder['basedir'];

			$backup = $rootPath.'/'.$filename;

			$zip = $this->init($backup);

			if( is_wp_error($zip) )
				wp_die( $zip->get_error_message() );

			if( file_exists($backup) )
				return $backup;

			if( $type == 'all' || $type == 'sql'){

				$db = $this->dumpDatabase($zip, $rootPath);
				if( is_wp_error($db) )
					wp_die( $db->get_error_message() );
			}

			if( $type == 'all' || $type == 'uploads'){

				$uploads = $this->dumpFolder($zip, $rootPath, ['wpallimport', 'cache', 'wpcf7_uploads', 'wp-personal-data-exports'], '/(?!.*150x150).*-[0-9]+x[0-9]+(-c-default|-c-center)?\.[a-z]{3,4}$/');
				if( is_wp_error($uploads) )
					wp_die( $uploads->get_error_message() );
			}

			$zip->close();

			if( $type == 'all' || $type == 'sql')
				unlink($rootPath.'/db.sql');

			if( file_exists($backup) )
				return $backup;
			else
				wp_die('Can\'t generate archive file');
		}

		return $backup;
	}


	/**
	 * Remove all thumbnails
	 */
	private function download($global=false, $type='all')
	{
		@ini_set('max_execution_time', 60);

		$filename = 'backup-'.date('Ymd').'.zip';

		if ( current_user_can('administrator') && (!$global || is_super_admin()) )
		{
			if( $backup = $this->create($global, $type, $filename) )
			{
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename='.basename($backup));
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header('Content-Length: ' . filesize($backup));

				ignore_user_abort(true);

				$myInputStream = fopen($backup, 'rb');
				$myOutputStream = fopen('php://output', 'wb');

				stream_copy_to_stream($myInputStream, $myOutputStream);

				fclose($myOutputStream);
				fclose($myInputStream);

				unlink($backup);

				exit(0);
			}
		}

		wp_redirect( get_admin_url(null, $global?'network/settings.php':'options-general.php') );
	}


	/**
	 * add network parameters
	 */
	public function wpmuOptions()
	{
		echo '<table id="backup" class="form-table">
			<tbody><tr>
				<th scope="row"><h2>'.__('Backup').'</h2></th>
				<td>
				  <a class="button button-primary" href="'.get_admin_url().'?download_mu_backup&type=all">'.__('Download All').'</a>
				  <a class="button button-primary" href="'.get_admin_url().'?download_mu_backup&type=sql">'.__('Download SQL').'</a>
				  <a class="button button-primary" href="'.get_admin_url().'?download_mu_backup&type=uploads">'.__('Download Uploads').'</a>
				</td>
			</tr>
		</tbody></table>';
	}


	/**
	 * add admin parameters
	 */
	public function adminInit()
	{
		if( isset($_GET['download_backup']) )
			$this->download(false, isset($_GET['type'])?$_GET['type']:'all');

		if( isset($_GET['download_mu_backup']) )
			$this->download(true, isset($_GET['type'])?$_GET['type']:'all');

		add_settings_field('download_backup', __('Database'), function(){

			echo '<a class="button button-primary" href="'.get_admin_url().'?download_backup&type=sql">'.__('Download backup').'</a> ';

		}, 'general');

		add_settings_field('download_backup', __('Uploads'), function(){

			echo '<a class="button button-primary" href="'.get_admin_url().'?download_backup&type=uploads">'.__('Download backup').'</a>';

		}, 'media');

	}
	
	public function __construct($config)
	{
		$this->config = $config;

		if( !is_admin() or (isset($_SERVER['BACKUP']) && !$_SERVER['BACKUP']) )
			return;

		add_action( 'admin_init', [$this, 'adminInit'] );
		add_action( 'wpmu_options', [$this, 'wpmuOptions'] );
	}
}
