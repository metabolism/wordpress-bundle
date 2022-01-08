<?php

namespace Metabolism\WordpressBundle\Entity;


/**
 * Class Menu
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class MenuItem extends Entity
{
	public $entity = 'menu-item';

    /** @var MenuItem[] $children */
    public $children;

	public $classes;
	public $class;
	public $description;
    public $link;
    public $menu_order;
    public $menu_item_parent;
    public $object;
    public $object_id;
    public $target;
	public $title;
	public $type;
	public $current;
	public $current_item_ancestor;
	public $current_item_parent;

    private $post;

    public function __toString()
    {
        return '<a href="'.$this->link.'" target="'.$this->target.'">'.$this->title.'</a>';
    }

	/**
	 * MenuItem constructor.
	 * @param \WP_Post $post
	 * @param array $args
	 */
	public function __construct($post, $args = [] ) {
		
		if ( $post ){

			$this->post = $post;

			$this->ID = $post->ID;
			$this->object_id = intval($post->object_id);
			$this->menu_item_parent = $post->menu_item_parent;
            $this->menu_order = $post->menu_order;
            $this->link = $post->url;
			$this->title = $post->title;
			$this->target = $post->target;
			$this->class = implode(' ', $post->classes);
			$this->classes = $post->classes;
			$this->object = $post->object;
			$this->type = $post->type;
			$this->description = $post->description;
            $this->current = $post->current;
            $this->current_item_ancestor = $post->current_item_ancestor;
            $this->current_item_parent = $post->current_item_parent;

			$this->loadMetafields($this->ID, 'menuItem');
		}
	}
}
