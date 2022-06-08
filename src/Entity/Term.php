<?php

namespace Metabolism\WordpressBundle\Entity;

use Metabolism\WordpressBundle\Factory\Factory;
use Metabolism\WordpressBundle\Factory\PostFactory;
use Metabolism\WordpressBundle\Factory\TermFactory;
use Metabolism\WordpressBundle\Repository\PostRepository;

/**
 * Class Term
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Term extends Entity
{
	public $entity = 'term';

    public $current;
    public $count;
    public $taxonomy;
    public $slug;
    public $title;
    public $group;
    public $content;

    /** @var Term[] */
    protected $children;
    protected $depth;
    protected $excerpt;
    protected $link;
    /** @var Term */
    protected $parent;
    protected $template;
    protected $thumbnail;
    protected $ancestors;

    /** @var \WP_Term|bool */
    protected $term;

    public function __toString(){

        return $this->title??'Invalid term';
    }

	/**
	 * Post constructor.
	 *
	 * @param null $id
	 */
	public function __construct($id){

        if( is_array($id) ) {

			if( empty($id) || isset($id['invalid_taxonomy']) )
				return;

			$id = $id[0];
		}

		if( $term = $this->get($id) ) {

            $this->ID = $term->term_id;
            $this->current = get_queried_object_id() == $this->ID;
            $this->taxonomy = $term->taxonomy;
            $this->count = $term->count;
            $this->slug = $term->slug;
            $this->title = $term->name;
            $this->group = $term->term_group;
            $this->content = nl2br($term->description);

            $this->loadMetafields($this->ID, $this->taxonomy);
		}
	}


    /**
     * Has parent term
     *
     * @return bool
     */
    public function hasParent() {

        return $this->term->parent > 0;
    }


	/**
	 * @param $pid
	 * @return \WP_Term|false
	 */
	protected function get($pid ) {

		if( $term = get_term($pid) ) {

			if( is_wp_error($term) || !$term )
				return false;

			$this->term = $term;
		}

		return $term;
	}


	/**
     * Get term children
     *
	 * @return Term[]
     */
	protected function getChildren( ) {

        if( is_null($this->children) ){

            $terms_id = get_term_children( $this->ID, $this->taxonomy );

            foreach ($terms_id as $term_id)
                $this->children[] = TermFactory::create($term_id);
        }

        return $this->children;
	}


	/**
     * Get term posts
     *
	 * @return Post[]
     */
	protected function getPosts($orderBy=null, $limit=null, $offset=null) {

        $postRepository = new PostRepository();

        return $postRepository->findBy(['tax_query' => [[
            'taxonomy' => $this->taxonomy,
            'field' => 'slug',
            'terms' => [$this->slug],
            'operator' => 'IN'
        ]]], $orderBy, $limit, $offset);
	}


    /**
     * Get parent term
     *
     * @return Term|false
     */
    public function getParent() {

        if( is_null($this->parent) )
            $this->parent = TermFactory::create($this->term->parent);

        return $this->parent;
    }

    public function getLink(){

        if( is_null($this->link) )
            $this->link = get_term_link( $this->term );

        return $this->link;
    }

    public function getDepth(){

        if( is_null($this->depth) )
            $this->depth = count(get_ancestors( $this->ID, $this->taxonomy ));

        return $this->depth;
    }

    /**
     * Get term ancestors
     *
     * @param $reverse
     * @return array|Term[]
     */
    public function getAncestors($reverse=true){

        if( is_null($this->ancestors) ){

            $parents_id = get_ancestors($this->ID, $this->taxonomy);

            if( $reverse )
                $parents_id = array_reverse($parents_id);

            $ancestors = [];

            foreach ($parents_id as $term_id)
                $ancestors[] = TermFactory::create($term_id);

            $this->ancestors = $ancestors;
        }

        return $this->ancestors;
    }

    public function getTemplate(){

        if( is_null($this->template) )
            $this->template =  get_term_meta($this->ID, 'template', true);

        return $this->template;
    }

    public function getExcerpt(){

        if( is_null($this->excerpt) )
            $this->excerpt = strip_tags(term_description($this->ID),'<b><i><strong><em><br>');

        return $this->excerpt;
    }

    public function getThumbnail(){

        //todo: move to ACFHelper Provider using action
        if( is_null($this->thumbnail) && function_exists('get_field_object') ){

            $object = get_field_object('thumbnail', $this->taxonomy.'_'.$this->ID);

            if( $object && $object['value'] ){

                $id = $object['return_format'] == 'array' ? $object['value']['id'] : $object['value'];
                $this->thumbnail = Factory::create( $id, 'image');
            }
        }

        return $this->thumbnail;
    }

    /**
     * Set term meta
     *
     * @param $key
     * @param $value
     * @param $prev_value
     * @return void
     */
    public function setMeta($key, $value, $prev_value=''){

        if( $value )
            update_term_meta($this->ID, $key, $value, $prev_value);
        else
            delete_term_meta($this->ID, $key);
    }
}
