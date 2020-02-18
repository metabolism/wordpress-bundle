<?php

namespace Metabolism\WordpressBundle\Plugin;


use Dflydev\DotAccessData\Data;
use Metabolism\WordpressBundle\Traits\SingletonTrait;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class RewritePlugin {

	use SingletonTrait;

	private $config;

	/**
	 * Add search post type filtered
	 */
	public function add(){

		global $wp_rewrite;

		$search_slug = get_option( 'search_rewrite_slug' );

		if( !empty($search_slug) )
			$wp_rewrite->search_base = $search_slug;

		$search_post_type_permastuct = str_replace('/%search%', '/%post_type%/%search%', $wp_rewrite->get_search_permastruct());
		$regex = str_replace('%search%', '([^/]*)', str_replace('%post_type%', '([^/]*)', $search_post_type_permastuct));
		add_rewrite_rule('^'.$regex.'/'.$wp_rewrite->pagination_base.'/([0-9]{1,})/?', 'index.php?s=$matches[2]&post_type=$matches[1]&paged=$matches[3]', 'top');
		add_rewrite_rule('^'.$regex.'/?', 'index.php?s=$matches[2]&post_type=$matches[1]', 'top');

		$wp_rewrite->search_post_type_structure = $search_post_type_permastuct;
	}

	/**
	 * Remove rules based on config
	 * @param $wp_rewrite
	 */
	public function remove($wp_rewrite ){

		$remove = $this->config->get('rewrite_rules.remove', []);

		foreach ($wp_rewrite->rules as $rule=>$rewrite){

			if( in_array('attachment', $remove) && strpos($rule, '/attachment/') !== false )
				unset( $wp_rewrite->rules [$rule] );

			if( in_array('embed', $remove) && strpos($rule, '/embed/') !== false )
				unset( $wp_rewrite->rules [$rule] );

			if( in_array('feed', $remove) && (strpos($rule, '/(feed|rdf|rss|rss2|atom)/') !== false || strpos($rule, '/feed/') !== false) )
				unset( $wp_rewrite->rules [$rule] );

			if( in_array('trackback', $remove) && strpos($rule, '/trackback/') !== false )
				unset( $wp_rewrite->rules [$rule] );

			if( in_array('comment', $remove) && strpos($rule, '/comment-page-') !== false )
				unset( $wp_rewrite->rules [$rule] );
		}
	}

	/**
	 * RewritePlugin constructor.
	 * @param Data $config
	 */
	function __construct($config) {

		$this->config = $config;

		add_action( 'generate_rewrite_rules', [$this, 'remove'] );
		add_action( 'init', [$this, 'add']);
	}
}
