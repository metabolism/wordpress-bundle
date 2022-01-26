<?php

namespace Metabolism\WordpressBundle\Helper;

use Metabolism\WordpressBundle\Entity\Post;
use Metabolism\WordpressBundle\Entity\Term;
use Metabolism\WordpressBundle\Factory\PostFactory;
use Metabolism\WordpressBundle\Factory\TermFactory;

/**
 * Class QueryHelper
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class QueryHelper
{
	
	/**
     * @deprecated
     *
	 * QueryHelper terms
	 * @param array $args see https://developer.wordpress.org/reference/classes/wp_term_query/__construct/
	 * @return Term[]
	 */
	public static function get_terms($args=[])
	{
        trigger_error('Method ' . __METHOD__ . ' is deprecated use $postRepository->findBy($args) instead', E_USER_DEPRECATED);

        if( !isset($args['depth']) || !is_int($args['depth']))
			$args['depth'] = 1;

		$args['fields'] = 'ids';

		$terms = get_terms( $args );

		foreach ($terms as &$term) {
			$term = TermFactory::create( $term );
		}

		return is_wp_error($terms) ? [] : array_filter($terms);
	}

	/**
     * @deprecated
     *
	 * @param $pid
	 * @param array $args
	 * @param bool $loop
	 * @return array|bool
	 */
	public static function get_adjacent_posts($pid, $args=[], $loop=false)
	{
        trigger_error('Method ' . __METHOD__ . ' is deprecated use $post->adjacents($args, $loop) instead', E_USER_DEPRECATED);

        if( !$post = get_post($pid) )
			return false;

		$default_args = [
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'post_type' => $post->post_type
		];

		$args = array_merge($default_args, $args);

		$query = new \WP_Query($args);

		$next_id = $prev_id = false;

		foreach($query->posts as $key => $_post_id) {
			if($_post_id == $post->ID){
				$next_id = $query->posts[$key + 1] ?? false;
				$prev_id = $query->posts[$key - 1] ?? false;
				break;
			}
		}

		if( !$next_id && $loop )
			$next_id = $query->posts[0];

		if( !$prev_id && $loop )
			$prev_id = $query->posts[ count($query->posts) - 1 ];

		return [
			'next' => $next_id ? PostFactory::create($next_id) : false,
			'prev' => $prev_id ? PostFactory::create($prev_id) : false
		];
	}


	/**
     * @deprecated
     *
	 * @param $field
	 * @param $value
	 * @param $taxonomy
	 * @return bool|Term|\WP_Error
	 */
	public static function get_term_by($field, $value, $taxonomy)
	{
        trigger_error('Method ' . __METHOD__ . ' is deprecated use $termRepository->findOneBy($args) instead', E_USER_DEPRECATED);

		$term = get_term_by( $field, $value, $taxonomy );

		if( $term )
			return TermFactory::create( $term );
		else
			return false;
	}


	/**
     * @deprecated
     *
	 * @param array $args
	 * @return Post|bool|\WP_Error
	 */
	public static function get_post($args=[])
	{
        trigger_error('Method ' . __METHOD__ . ' is deprecated use $postRepository->findOneBy($args) instead', E_USER_DEPRECATED);

        if( empty($args) )
			return PostFactory::create();

		if( !is_array($args) )
			return PostFactory::create($args);

		$args['posts_per_page'] = 1;

		$posts = self::get_posts($args);

		if( count($posts) )
			return $posts[0];

		return false;
	}


	/**
     * @deprecated
     *
	 * @param array $args
	 * @return Post[]
	 */
	public static function get_posts($args=[])
	{
        trigger_error('Method ' . __METHOD__ . ' is deprecated use $postRepository->findBy($args) instead', E_USER_DEPRECATED);

        $query = self::wp_query($args);

		return $query ? array_filter($query->posts) : [];
	}


	/**
     * @deprecated
     *
	 * @param array $args
	 * @return bool|mixed
	 */
	public static function wp_query($args=[])
	{
        trigger_error('Method ' . __METHOD__ . ' is deprecated use $postRepository->findBy($args) instead', E_USER_DEPRECATED);

        global $wp_query;

		if( empty($args) ) {
			$query = $wp_query;
		}
		else {
			if( !isset($args['post_type']) )
				$args = array_merge($wp_query->query, $args);

			if( !isset($args['posts_per_page']) && !isset($args['numberposts']))
				$args['posts_per_page'] = get_option( 'posts_per_page' );

			$args['fields'] = 'ids';

			$query = new \WP_Query( $args );
		}

		if( !isset($query->posts) || !is_array($query->posts) )
			return false;

		foreach ($query->posts as &$post) {
			$post = PostFactory::create( $post );
		}

		return $query;
	}
}
