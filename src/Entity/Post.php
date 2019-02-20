<?php

namespace Metabolism\WordpressBundle\Entity;

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

	/** @var Image */
	public $thumbnail = false;

	public $link = '';
	public $template = '';
	public $comment_status;
	public $menu_order;
	public $comment_count;
	public $author;
	public $date;
	public $date_gmt;
	public $modified;
	public $modified_gmt;
	public $title;
	public $status;
	public $password;
	public $parent;
	public $type;
	public $name;

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


	protected function get( $pid ) {

		if( $post = get_post($pid) )
		{
			if( !$post || is_wp_error($post) )
				return false;

			$this->_post = clone $post;

			if( WP_FRONT )
				$post->link = get_permalink( $post );

			$post->template = get_page_template_slug( $post );
			$post->thumbnail = get_post_thumbnail_id( $post );

			if( $post->thumbnail )
				$post->thumbnail = PostFactory::create($post->thumbnail, 'image');
		}

		return $post;
	}


	protected function getSibling($direction='prev', $in_same_term , $excluded_terms, $taxonomy){

		global $post;
		$old_global = $post;
		$post = $this->_post;

		if( $direction === 'prev')
			$sibling = get_previous_post($in_same_term , $excluded_terms, $taxonomy);
		else
			$sibling = get_next_post($in_same_term , $excluded_terms, $taxonomy);

		$post = $old_global;

		if( $sibling && $sibling instanceof \WP_Post)
			return PostFactory::create($sibling->ID);
		else
			return false;
	}


	public function next($in_same_term = false, $excluded_terms = '', $taxonomy = 'category') {

		if( !is_null($this->_next) )
			return $this->_next;

		$this->_next = $this->getSibling('next', $in_same_term , $excluded_terms, $taxonomy);

		return $this->_next;
	}


	public function prev($in_same_term = false, $excluded_terms = '', $taxonomy = 'category') {

		if( !is_null($this->_prev) )
			return $this->_prev;

		$this->_prev = $this->getSibling('prev', $in_same_term , $excluded_terms, $taxonomy);

		return $this->_prev;
	}


	public function get_term( $tax='' ) {

		$term = false;

		if ( class_exists('WPSEO_Primary_Term') )
		{
			$wpseo_primary_term = new \WPSEO_Primary_Term( $tax, $this->ID );

			if( $wpseo_primary_term )
				$term = $wpseo_primary_term->get_primary_term();
		}

		if(!$term){
			$terms = get_the_terms($this->ID, $tax);
			if( $terms && !is_wp_error($terms) && count($terms) )
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

			if( is_wp_error($terms) ){

				$term_array[$taxonomy] = $terms->get_error_messages();
			}
			else
			{
				foreach ($terms as $term)
					$term_array[$taxonomy][$term] = TaxonomyFactory::create($term);
			}
		}

		if( count($taxonomies) == 1 )
			return end($term_array);
		else
			return $term_array;
	}
}
