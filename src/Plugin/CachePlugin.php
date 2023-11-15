<?php

namespace Metabolism\WordpressBundle\Plugin;

/**
 * Class
 */
class CachePlugin
{
	private $noticeMessage,  $errorMessage;
	private $home_url;


	/**
	 * Purge post url from id
	 *
	 * @param bool $post_id
	 * @return void
	 */
	public function purgePostCache($post_id, $post, $update)
	{
		if( $post_id && $update){

			if( wp_is_post_revision( $post_id ) || (defined( 'DOING_AUTOSAVE' ) and DOING_AUTOSAVE) )
				return;

			$post = get_post($post_id);

			if( $post && $post->post_status === 'publish' && is_post_type_viewable($post->post_type) ){

				$url = get_permalink($post_id);

				$this->purge($url);
			}
		}
	}

	/**
	 * Purge term url from id
	 *
	 * @param bool $term_id
	 * @return void
	 */
	public function purgeTermCache($term_id, $tt_id, $taxonomy, $update)
	{
		if( $term_id && $update ){

			if( defined( 'DOING_AUTOSAVE' ) and DOING_AUTOSAVE )
				return;

			$term = get_term($term_id);

			if( $term && is_taxonomy_viewable($term->taxonomy) ){

				$url = get_term_link($term_id);

				$this->purge($url);
			}
		}
	}


	public function message()
	{
		$html = '';

		if( !empty($this->noticeMessage) ){

			$html .= '<div id="message" class="updated fade"><p><strong>' . __('Cache') . '</strong><br />';

			foreach ( $this->noticeMessage as $message )
				$html .= $message.'<br/>';

			$html .= '</p></div>';
		}

		if( !empty($this->errorMessage) ){

			$html .= '<div id="message" class="error fade"><p><strong>' . __('Cache') . '</strong><br />';

			foreach ( $this->errorMessage as $message )
				$html .= $message.'<br/>';

			$html .= '</p></div>';
		}

		echo $html;
	}


	/**
	 * Reset cache
	 */
	public function reset()
	{
		$this->purge();
		$this->clear();
	}

	/**
	 * Purge cache
	 * @param bool $url
	 * @return array
	 */
	public static function purgeUrl($url=false){

		if( !$url )
			$url = get_home_url(null, '.*');

		$varnish_ssl = $_SERVER['VARNISH_SSL'] ?? false;
		$result = [];

		$args = [
			'method' => 'PURGE',
			'headers' => [
				'host' => $_SERVER['HTTP_HOST'],
				'X-VC-Purge-Method' => 'regex',
				'X-VC-Purge-Host' => $_SERVER['HTTP_HOST']
			],
			'sslverify' => false
		];

		if( isset($_SERVER['VARNISH_IPS']) ){

			$varnish_ips = explode(',',$_SERVER['VARNISH_IPS']);
		}
		elseif( isset($_SERVER['VARNISH_IP']) ){

			$varnish_ips = [$_SERVER['VARNISH_IP']];
		}
		else{

			$response = wp_remote_request(str_replace('.*', '*', $url), $args);
			$result[] = ['url'=>$url, 'request'=>$response];

			return $result;
		}

		foreach ($varnish_ips as $varnish_ip){

			$varnish_url = str_replace($_SERVER['HTTP_HOST'], $varnish_ip, $url);

			if( !$varnish_ssl )
				$varnish_url = str_replace('https://', 'http://', $varnish_url);

			$response = wp_remote_request($varnish_url, $args);
			$result[] = ['url'=>$varnish_url, 'request'=>$response];
		}

		return $result;
	}


	/**
	 * Purge cache
	 * @param bool $url
	 */
	private function purge($url=false)
	{
		if( WP_DEBUG || strpos($url, $this->home_url) === false )
			return;

		$results = self::purgeUrl($url);

		foreach ($results as $result){

			if ( is_wp_error($result['request']) )
				$this->errorMessage[] = $result['url'].' : '.$result['request']->get_error_code().' '.$result['request']->get_error_message();
			elseif($result['request']['response']['code'] >= 300)
				$this->errorMessage[] = $result['url'].' : '.$result['request']['response']['code'].' '.$result['request']['response']['message'];
			else
				$this->noticeMessage[] = $result['url'].' : '.$result['request']['response']['code'].' '.$result['request']['response']['message'];
		}

		add_action('admin_notices', [$this, 'message'], 999);
	}


	/**
	 * Clear cache
	 */
	private function clear()
	{
		if ( !self::cacheFlush() )
			$this->errorMessage[] = 'Unable to clear cache';
		else
			$this->noticeMessage[] = 'Cleared';

		add_action('admin_notices', [$this, 'message'], 999);
	}


	/**
	 * Add maintenance button and checkbox
	 */
	public function addClearCacheButton()
	{
		add_action( 'admin_bar_menu', function( $wp_admin_bar )
		{
			$args = [
				'id'    => 'cache-purge',
				'title' => __('Purge cache'),
				'href'  => get_admin_url().'?cache=purge'
			];

			$wp_admin_bar->add_node( $args );

			if ( current_user_can('administrator') ){

				$args = [
					'id'    => 'cache-clear',
					'title' => __('Clear cache'),
					'href'  => get_admin_url().'?cache=clear'
				];

				$wp_admin_bar->add_node( $args );
			}

		}, 999 );
	}


	/**
	 * Clear cache completely
	 */
	public static function cacheFlush(){

		wp_cache_flush();

		return self::rrmdir(BASE_URI.'/var/cache', true);
	}


	/**
	 * Recursive rmdir
	 * @param string $dir
	 * @return bool
	 */
	public static function rrmdir($dir, $keep=false) {

		$status = true;

		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (is_dir($dir."/".$object))
						$status = self::rrmdir($dir."/".$object) && $status;
					else
						$status = @unlink($dir."/".$object) && $status;
				}
			}

			if( !$keep )
				$status = @rmdir($dir) && $status;
		}

		return $status;
	}


	/**
	 * CachePlugin constructor.
	 */
	public function __construct()
	{
		$this->home_url = home_url();

		if( !WP_DEBUG ) {

			if( isset($_GET['cache']) && $_GET['cache'] == 'purge' )
				$this->purge();

			if( isset($_GET['cache']) && $_GET['cache'] == 'clear' )
				$this->clear();

			add_action( 'init', [$this, 'addClearCacheButton']);
			add_action( 'save_post', [$this, 'purgePostCache'], 10, 3);
			add_action( 'saved_term', [$this, 'purgeTermCache'], 10, 4);
		}

		add_action( 'reset_cache', [$this, 'reset']);
	}
}
