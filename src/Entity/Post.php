<?php

namespace Metabolism\WordpressBundle\Entity;

use Metabolism\WordpressBundle\Entity\Image;
use Metabolism\WordpressBundle\Entity\Term;
use Metabolism\WordpressBundle\Factory\PostFactory;
use Metabolism\WordpressBundle\Factory\TaxonomyFactory;

/**
 * Class Post
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Post extends Entity
{
	public $excerpt ='';
	public $thumbnail = false;
	public $link = '';
	public $template = '';

	private $_next = null;
	private $_prev = null;
	private $_post = null;

	/**
	 * Post constructor.
	 *
	 * @param null $id
	 */
	public function __construct($id = null) {

		if( $post = $this->get($id) )
		{
			$this->import($post, false, 'post_');
			$this->addCustomFields($id);
		}
	}


	private function get( $pid ) {

		$post = false;

		if( $post = get_post($pid) )
		{
			if( !$post || is_wp_error($post) )
				return false;

			$this->_post = $post;

			$post->link = get_permalink( $post );
			$post->template = get_page_template_slug( $post );
			$post->thumbnail = get_post_thumbnail_id( $post );

			if( $post->thumbnail )
				$post->thumbnail = PostFactory::create($post->thumbnail, 'image');
		}

		return $post;
	}


	public function next($in_same_term = false, $excluded_terms = '', $taxonomy = 'category') {

		if( !is_null($this->_next) )
			return $this->_next;

		global $post;
		$old_global = $post;
		$post = $this->_post;

		$_next = get_next_post($in_same_term , $excluded_terms, $taxonomy);

		$post = $old_global;

		if( $_next )
			$this->_next = PostFactory::create($_next->ID);

		return $this->_next;
	}


	public function prev($in_same_term = false, $excluded_terms = '', $taxonomy = 'category') {

		if( !is_null($this->_next) )
			return $this->_next;

		global $post;
		$old_global = $post;
		$post = $this->_post;

		$_next = get_previous_post($in_same_term , $excluded_terms, $taxonomy);

		$post = $old_global;

		if( $_next )
			$this->_next = PostFactory::create($_next->ID);

		return $this->_next;
	}


	public function get_term( $tax='category' ) {

		$term = false;

		if ( class_exists('WPSEO_Primary_Term') )
		{
			$wpseo_primary_term = new \WPSEO_Primary_Term( $tax, $this->ID );

			if( $wpseo_primary_term )
				$term = $wpseo_primary_term->get_primary_term();
		}
		else {
			$terms = get_the_terms($this->ID, $tax);
			if( count($terms) )
				$term = $terms[0];
		}

		if( $term )
			return TaxonomyFactory::create( $term );
		else
			return false;
	}


	public function get_terms( $tax = '' ) {

		$taxonomies = array();

		if ( is_array($tax) )
		{
			$taxonomies = $tax;
		}
		if ( is_string($tax) )
		{
			if ( in_array($tax, ['all', 'any', '']) )
				$taxonomies = get_object_taxonomies($this->type);
			else
				$taxonomies = [$tax];
		}

		$term_array = [];

		foreach ( $taxonomies as $taxonomy )
		{
			if ( in_array($taxonomy, ['tag', 'tags']) )
				$taxonomy = 'post_tag';
			if ( $taxonomy == 'categories' )
				$taxonomy = 'category';

			$terms = wp_get_post_terms($this->ID, $taxonomy, ['fields' => 'ids']);

			foreach ($terms as $term)
				$term_array[$term] = TaxonomyFactory::create($term);
		}

		return $term_array;
	}
}
