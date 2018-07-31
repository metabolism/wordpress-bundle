<?php

namespace Metabolism\WordpressBundle\Plugin;

use Ifsnop\Mysqldump as IMysqldump;
use Metabolism\WordpressBundle\Helper\DirFilterHelper;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class BackupPlugin {

	protected $config;

	private function dumpFolder($source, $destination, $exclude = [], $exclude_pattern=false)
	{
		if ( !extension_loaded( 'zip' ) )
			return new \WP_Error('zip_extension', 'Zip Extension is not loaded');

		if ( is_string( $source ) )
			$source_arr = [$source];
		else
			$source_arr = $source;

		$zip = new \ZipArchive();

		if ( !$zip->open( $destination, \ZipArchive::CREATE ) )
			return new \WP_Error('archive', 'Can\'t create archive file');

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

							$zip->addFile( $file, $folder . str_replace( $source . '/', '', $file ) );
						}
					}
				}
			}
			else {

				if ( is_file( $source ) === true ) {

					$zip->addFile( $source, $folder . basename( $source ) );
				}
			}
		}

		return $zip->close();
	}


	/**
	 * Remove all thumbnails
	 */
	private function dumpDatabase($file)
	{
		try {

			$dump = new IMysqldump\Mysqldump('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD, ['add-drop-table' => true]);
			$dump->start($file);

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
	private function create($all=false, $filename)
	{
		$backup = false;

		if ( current_user_can('administrator') && (!$all || is_super_admin()) )
		{
			$folder = wp_upload_dir();
			$rootPath = $folder['basedir'];

			$backup   = $rootPath.'/'.$filename;

			if( file_exists($backup) )
			{
				if( file_exists($rootPath.'/db.sql') )
					unlink($rootPath.'/db.sql');

				return $backup;
			}

			$this->dumpDatabase($rootPath.'/db.sql');
			$this->dumpFolder($rootPath, $backup, ['wpallimport', 'cache', 'wpcf7_uploads'], '/(?!.*150x150).*-[0-9]+x[0-9]+(-c-default|-c-center)?\.[a-z]{3,4}$/');

			unlink($rootPath.'/db.sql');

			return $backup;
		}

		return $backup;
	}


	/**
	 * Remove all thumbnails
	 */
	private function download($all=false)
	{
		ini_set('max_execution_time', 300);

		$filename = 'backup-'.date('Ymd').'.zip';

		if ( current_user_can('administrator') && (!$all || is_super_admin()) )
		{
			if( $backup = $this->create($all, $filename) )
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

		wp_redirect( get_admin_url(null, $all?'network/settings.php':'options-general.php') );
	}


	/**
	 * add network parameters
	 */
	public function wpmuOptions()
	{
		echo '<h2>Backup</h2>';
		echo '<table id="backup" class="form-table">
			<tbody><tr>
				<th scope="row">'.__('Download backup').'</th>
				<td><a class="button button-primary" href="'.get_admin_url().'?download_mu_backup">Download</a></td>
			</tr>
		</tbody></table>';
	}


	/**
	 * add admin parameters
	 */
	public function adminInit()
	{
		if( isset($_GET['download_backup']) )
			$this->download();

		if( isset($_GET['download_mu_backup']) )
			$this->download(true);

		// Remove generated thumbnails option
		add_settings_field('download_backup', __('Backup'), function(){

			echo '<a class="button button-primary" href="'.get_admin_url().'?download_backup">'.__('Download').'</a>';

		}, 'general');

	}
	
	public function __construct($config)
	{
		$this->config = $config;

		if( !is_admin() )
			return;

		add_action( 'init', function()
		{
			add_action( 'admin_init', [$this, 'adminInit'] );
			add_action( 'wpmu_options', [$this, 'wpmuOptions'] );
		});
	}
}
