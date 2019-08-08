<?php

namespace Metabolism\WordpressBundle\Entity;

use Metabolism\WordpressBundle\Factory\Factory;
use Metabolism\WordpressBundle\Factory\PostFactory;
use Metabolism\WordpressBundle\Factory\TaxonomyFactory;
use Metabolism\WordpressBundle\Helper\Query;

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
	public $slug;
	public $name;
	public $content;

	private $_next = null;
	private $_prev = null;
	private $_post = null;


	/**
	 * Post constructor.
	 *
	 * @param null $id
	 */
	public function __construct($id = null) {

		if( $post = $this->get($id) ) {

			$this->import($post, false, 'post_');
			$this->content = wpautop($this->content);

			$this->addCustomFields($post->ID);
		}
	}
	

	/**
	 * Validate class
	 * @param \WP_Post $post
	 * @return bool
	 */
	protected function isValidClass($post){

		$class =  explode('\\', get_class($this));
		$class =  end($class);

		return $class == "Post" || Factory::getClassname($post->post_type) == $class;
	}


	/**
	 * @param $pid
	 * @return array|bool|\WP_Post|null
	 */
	protected function get($pid ) {

		if( $post = get_post($pid) ) {

			if( is_wp_error($post) || !$this->isValidClass($post) )
				return false;

			$this->_post = clone $post;

			if( WP_FRONT )
				$post->link = get_permalink( $post );

			$post->template = get_page_template_slug( $post );
			$post->thumbnail = get_post_thumbnail_id( $post );

			if( $post->thumbnail )
				$post->thumbnail = Factory::create($post->thumbnail, 'image');

			$post->slug = $post->post_name;
		}

		return $post;
	}


	/**
	 * Get sibling post using wordpress natural order
	 * @param $args
	 * @param $loop
	 * @return array[
	 *  'prev' => Post,
	 *  'next' => Post
	 * ]|false
	 */
	public function adjacents($args=[], $loop=false){

		return Query::get_adjacent_posts($this->ID, $args, $loop);
	}


	/**
	 * Get sibling post using date order
	 * @param $direction
	 * @param $in_same_term
	 * @param $excluded_terms
	 * @param $taxonomy
	 * @return Post|false
	 */
	protected function getSibling($direction, $in_same_term = false , $excluded_terms = '', $taxonomy = 'category'){

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


	/**
	 * Get next post
	 * See: https://developer.wordpress.org/reference/functions/get_next_post/
	 *
	 * @param bool $in_same_term
	 * @param string $excluded_terms
	 * @param string $taxonomy
	 * @return Post|false
	 */
	public function next($in_same_term = false, $excluded_terms = '', $taxonomy = 'category') {

		if( !is_null($this->_next) )
			return $this->_next;

		$this->_next = $this->getSibling('next', $in_same_term , $excluded_terms, $taxonomy);

		return $this->_next;
	}


	/**
	 * Get parent post
	 *
	 * @return Post|false
	 */
	public function getParent() {

		if( $this->parent )
			return PostFactory::create($this->parent);

		return false;
	}


	/**
	 * Get previous post
	 * See: https://developer.wordpress.org/reference/functions/get_previous_post/
	 *
	 * @param bool $in_same_term
	 * @param string $excluded_terms
	 * @param string $taxonomy
	 * @return Post|false
	 */
	public function prev($in_same_term = false, $excluded_terms = '', $taxonomy = 'category') {

		if( !is_null($this->_prev) )
			return $this->_prev;

		$this->_prev = $this->getSibling('prev', $in_same_term , $excluded_terms, $taxonomy);

		return $this->_prev;
	}


	/**
	 * Get term
	 * See: https://developer.wordpress.org/reference/functions/get_the_terms/
	 *
	 * @param string $tax
	 * @return Term|bool
	 */
	public function getTerm( $tax='' ) {

		$term = false;

		$terms = get_the_terms($this->ID, $tax);
		if( $terms && !is_wp_error($terms) && count($terms) )
			$term = $terms[0];

		if( $term )
			return TaxonomyFactory::create( $term );
		else
			return false;
	}

	/**
	 * Get term list
	 *
	 * @param string $tax
	 * @return Term[]|[]
	 */
	public function getTerms( $tax = '' ) {
		
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

	/**
	 * @deprecated
	 */
	public function get_terms( $tax='' ) { return $this->getTerms($tax); }

	/**
	 * @deprecated
	 */
	public function get_term( $tax='' ) { return $this->getTerm($tax); }
}