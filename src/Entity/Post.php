<?php

namespace Metabolism\WordpressBundle\Entity;

use Metabolism\WordpressBundle\Factory\Factory;
use Metabolism\WordpressBundle\Factory\PostFactory;
use Metabolism\WordpressBundle\Factory\TaxonomyFactory;
use Metabolism\WordpressBundle\Helper\QueryHelper;

/**
 * Class Post
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Post extends Entity
{
	public $entity = 'post';

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
	public $class;
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
	public $sticky;
	public $excerpt ='';

	private $_next = null;
	private $_prev = null;
	private $_post = null;
	private $args = [];


	/**
	 * Post constructor.
	 *
	 * @param null $id
	 * @param array $args
	 */
	public function __construct($id = null, $args = []) {

		$this->args = $args;

		if( $post = $this->get($id) ) {

			$this->import($post, false, 'post_');

			if( !isset($args['depth']) || $args['depth'] )
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

			if( !HEADLESS || URL_MAPPING )
				$post->link = get_permalink( $post );

			$post->template = get_page_template_slug( $post );
			$post->thumbnail = get_post_thumbnail_id( $post );
			$post->class = implode(' ', get_post_class());
			$post->sticky = is_sticky($pid);

			if( $post->thumbnail ){

				global $_config;
				$return_format = $_config->get('image.return_format', false);
				
				if( $return_format == 'url'){

					$attachment = wp_get_attachment_image_src($post->thumbnail, 'full');

					if( $attachment )
						$post->thumbnail = $attachment[0];
					else
						$post->thumbnail = false;
				}
				elseif( $return_format != 'id'){

					$post->thumbnail = Factory::create($post->thumbnail, 'image');
				}
			}

			$post->slug = $post->post_name;
			$post->post_content = do_shortcode(wpautop($post->post_content));
			$post->post_excerpt = get_the_excerpt($post);

			unset($post->post_name);
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

		return QueryHelper::get_adjacent_posts($this->ID, $args, $loop);
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
	 * Get post comments
	 *
	 * @param array $args
	 * @return Comment[]|[]
	 */
	public function getComments($args=[]) {

		$default_args = [
			'post_id'=> $this->ID,
			'status'=> 'approve',
			'type'=> 'comment',
			'fields'=> 'ids'
		];


		$args = array_merge($default_args, $args);

		$comments_id = get_comments($args);
		$comments = [];

		foreach ($comments_id as $comment_id)
		{
			$comments[$comment_id] = Factory::create($comment_id, 'comment');
		}

		foreach ($comments as $comment)
		{
			if( $comment && $comment->parent )
			{
				$comments[$comment->parent]->replies[] = $comment;
				unset($comments[$comment->ID]);
			}
		}

		return $comments;
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
	 * See: https://codex.wordpress.org/Function_Reference/wp_get_post_terms
	 *
	 * @param string $tax
	 * @param array $args
	 * @return Term|bool
	 */
	public function getTerm( $tax='category', $args=[] ) {

		$args['number'] = 1;
		$terms = $this->getTerms($tax, $args);

		if( is_array($terms) && count($terms) )
			return end($terms);
		else
			return false;
	}

	/**
	 * Get term list
	 * See : https://developer.wordpress.org/reference/classes/wp_term_query/__construct/
	 *
	 * @param string $tax
	 * @param array $args
	 * @return Term[]|[]
	 */
	public function getTerms( $tax='category', $args=[] ) {

		$args['fields'] = 'ids';

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

			$terms = wp_get_post_terms($this->ID, $taxonomy, $args);

			if( is_wp_error($terms) ){

				$term_array[$taxonomy] = $terms->get_error_messages();
			}
			else
			{
				foreach ($terms as $term){

					if( (!isset($args['hierarchical']) || $args['hierarchical']) && count($taxonomies)>1 )
						$term_array[$taxonomy][] = TaxonomyFactory::create($term);
					else
						$term_array[] = TaxonomyFactory::create($term);
				}
			}
		}
		
		return $term_array;
	}

	/**
	 * @param string $tax
	 * @param bool $args
	 * @return Term[]
	 * @deprecated
	 */
	public function get_terms( $tax='', $args=false ) { return $this->getTerms($tax, $args); }

	/**
	 * @param string $tax
	 * @param bool $args
	 * @return bool|Term
	 * @deprecated
	 */
	public function get_term( $tax='', $args=false ) { return $this->getTerm($tax, $args); }
}