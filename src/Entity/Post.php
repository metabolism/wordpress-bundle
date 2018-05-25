<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Metabolism\WordpressBundle\Entity;


use Metabolism\WordpressBundle\Entity\Image;
use Metabolism\WordpressBundle\Entity\Term;

/**
 * Class Post
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Post extends Entity
{
	public $excerpt, $thumbnail;
	private $_next, $_prev;

	/**
	 * Post constructor.
	 *
	 * @param null $id
	 */
	public function __construct($id = null) {

		$_next = $_prev = null;

		if( is_object($id) )
		{
			if( !isset($id->ID) )
				return false;

			$id = $id->ID;
		}

		if( $post = $this->get($id) )
		{
			$this->import($post);
			$this->addCustomFields($id);
		}
	}


	private function get( $pid ) {

		$post = false;

		if( is_int($pid) && $post = get_post($pid) )
		{
			$post->link = get_permalink($post);
			$post->thumbnail = get_post_thumbnail_id( $post );

			if( $post->thumbnail )
				$post->thumbnail = new Image($post->thumbnail);
		}

		return $post;
	}


	public function next($in_same_term = false, $excluded_terms = '', $taxonomy = 'category') {

		if( !is_null($this->_next) )
			return $this->_next;

		global $post;
		$old_global = $post;

		$_next = get_next_post($in_same_term , $excluded_terms, $taxonomy);

		if( $_next )
			$this->_next = new Post($_next->ID);

		return $this->_next;
	}


	public function prev($in_same_term = false, $excluded_terms = '', $taxonomy = 'category') {

		if( !is_null($this->_next) )
			return $this->_next;

		global $post;
		$old_global = $post;

		$_next = get_previous_post($in_same_term , $excluded_terms, $taxonomy);

		if( $_next )
			$this->_next = new Post($_next->ID);

		return $this->_next;
	}


	public function get_terms( $tax = '', $merge = true ) {

		$taxonomies = array();

		if ( is_array($tax) )
		{
			$taxonomies = $tax;
		}
		if ( is_string($tax) )
		{
			if ( in_array($tax, ['all', 'any', '']) )
			{
				$taxonomies = get_object_taxonomies($this->type);
			}
			else
			{
				$taxonomies = [$tax];
			}
		}

		$term_class_objects = array();

		foreach ( $taxonomies as $taxonomy )
		{
			if ( in_array($taxonomy, ['tag', 'tags']) )
				$taxonomy = 'post_tag';
			if ( $taxonomy == 'categories' )
				$taxonomy = 'category';

			$terms = wp_get_post_terms($this->ID, $taxonomy, ['fields' => 'ids']);

			foreach ($terms as &$term)
				$term = new Term($term);
		}

		return $terms;
	}
}
