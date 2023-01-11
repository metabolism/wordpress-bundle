<?php

namespace Metabolism\WordpressBundle\Entity;

use Metabolism\WordpressBundle\Factory\Factory;
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

	protected $current;
	protected $count;
	protected $taxonomy;
	protected $slug;
	protected $title;
	protected $group;
	protected $content;
	protected $children;
	protected $depth;
	protected $excerpt;
	protected $link;
	protected $parent;
	protected $template;
	protected $thumbnail;
	protected $ancestors;
	protected $path;
	protected $public;
    protected $post_types;

	/** @var \WP_Term|bool */
	protected $term;

	public function __toString(): string
    {

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
			$this->taxonomy = $term->taxonomy;
			$this->count = $term->count;
			$this->slug = $term->slug;
			$this->title = $term->name;
			$this->group = $term->term_group;
			$this->content = $term->description;

			$this->loadMetafields($this->ID, 'term');
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
	 * Get attached post types
	 *
     * @return array
	 */
	public function getPostTypes() {

        if( is_null($this->post_types) ){

            global $wp_taxonomies;
            $this->post_types = $wp_taxonomies[$this->getTaxonomy()]->object_type;
        }

		return $this->post_types;
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
	 * Get term path
	 *
	 * @return false|string
	 */
	public function getPath(){

		if( is_null($this->path) && $this->isPublic() ){

			$taxonomy_object = get_taxonomy($this->taxonomy);

			if( $rewrite_slug = $taxonomy_object->rewrite['slug']??false )
				$this->path = str_replace(get_home_url().'/'.$rewrite_slug.'/', '', $this->getLink());
			else
				$this->path = str_replace(get_home_url().'/', '', $this->getLink());
		}

		return $this->path;
	}

	/**
	 * @return bool
	 */
	public function isPublic(): bool
	{
		if( is_null($this->public) )
			$this->public = is_taxonomy_viewable($this->taxonomy);

		return $this->public;
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
	 * @return bool
	 */
	public function isCurrent(): bool
	{
		if( is_null($this->current) )
			$this->current = $this->ID == get_queried_object_id();

		return $this->current;
	}

	/**
	 * @return int
	 */
	public function getCount(): int
	{
		return $this->count;
	}

	/**
	 * @return string
	 */
	public function getTaxonomy(): string
	{
		return $this->taxonomy;
	}

	/**
	 * @return string
	 */
	public function getSlug(): string
	{
		return $this->slug;
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->title;
	}

	/**
	 * @return int|string
	 */
	public function getGroup()
	{
		return $this->group;
	}

	/**
	 * @param bool $nl2br
	 * @return string
	 */
	public function getContent(?bool $nl2br=true): string
	{
		return $nl2br?nl2br($this->content):$this->content;
	}


	/**
	 * Get term posts
	 *
	 * @return PostCollection
	 */
	public function getPosts($post_type='post', $orderBy=null, $limit=null, $offset=null) {

		$postRepository = new PostRepository();

		return $postRepository->findBy([
			'post_type'=>$post_type,
			'tax_query' => [[
				'taxonomy' => $this->taxonomy,
				'field' => 'slug',
				'terms' => [$this->slug],
				'operator' => 'IN'
			]]
		], $orderBy, $limit, $offset);
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

    /**
     * Get term link
     *
     * @return false|string
     */
	public function getLink(){

		if( is_null($this->link) )
			$this->link = get_term_link( $this->term );

		return $this->link;
	}


    /**
     * @deprecated
     * @return false|string
     */
    public function getUrl(){

        return $this->getLink();
    }

    /**
     * Get term depth
     *
     * @return int
     */
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

    /**
     * Get term template
     *
     * @return string
     */
	public function getTemplate(){

		if( is_null($this->template) )
			$this->template =  get_term_meta($this->ID, 'template', true);

		return $this->template;
	}

    /**
     * Get term excerpt
     *
     * @return string
     */
	public function getExcerpt(){

		if( is_null($this->excerpt) )
			$this->excerpt = strip_tags(term_description($this->ID),'<b><i><strong><em><br>');

		return $this->excerpt;
	}

	/**
	 * @param $width
	 * @param $height
	 * @param $args
	 * @return bool|Entity|mixed
	 */
	public function getThumbnail($width=0, $height=0, $args=[]){

		//todo: move to ACFHelper Provider using action
		if( is_null($this->thumbnail) && function_exists('get_field_object') ){

			$object = get_field_object('thumbnail', $this->taxonomy.'_'.$this->ID);

			if( $object && $object['value'] ){

				$id = $object['return_format'] == 'array' ? $object['value']['id'] : $object['value'];
				$this->thumbnail = Factory::create( $id, 'image');
			}
		}

		if( !func_num_args() || !$this->thumbnail ){

			return $this->thumbnail;
		}
		else{

			$args['resize'] = [$width, $height];
			return $this->thumbnail->edit($args);
		}
	}
}
