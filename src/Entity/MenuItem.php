<?php

namespace Metabolism\WordpressBundle\Entity;


use Metabolism\WordpressBundle\Factory\Factory;

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
	public $content;
    public $link;
    public $order;
    public $item_parent;
    public $object_id;
    public $target;
	public $title;
	public $type;
	public $attr_title;
	public $current;
	public $current_item_ancestor;
	public $current_item_parent;

    protected $object;
    private $item;

    public function __toString()
    {
        return $this->title ? '<a href="'.$this->link.'" target="'.$this->target.'">'.$this->title.'</a>' : '';
    }

	/**
	 * MenuItem constructor.
	 * @param object $menu_item
	 */
	public function __construct($menu_item) {

		if ( $menu_item ){

			$this->item = $menu_item;

			$this->ID = $menu_item->ID;
			$this->object_id = intval($menu_item->object_id);
			$this->item_parent = $menu_item->menu_item_parent;
            $this->order = $menu_item->menu_order;
            $this->link = $menu_item->url;
			$this->title = $menu_item->title;
			$this->target = $menu_item->target;
			$this->class = trim(implode(' ', $menu_item->classes));
			$this->classes = $menu_item->classes;
			$this->type = $menu_item->type;
			$this->attr_title = $menu_item->attr_title;
			$this->content = nl2br($menu_item->post_content);
            $this->current = $menu_item->current;
            $this->current_item_ancestor = $menu_item->current_item_ancestor;
            $this->current_item_parent = $menu_item->current_item_parent;

			$this->loadMetafields($this->ID, 'post');
		}
	}

    public function getObject(){

        if( is_null($this->object) ){

            $default_class = $class = false;

            if( $this->type == 'custom' ){

                $this->object = false;
                return $this->object;
            }
            if( $this->type == 'post_type' ){

                $default_class = 'post';
                $class = $this->item->object;
            }
            elseif( $this->type == 'taxonomy' ){

                $default_class = 'term';
                $class = $this->item->object;
            }

            $this->object = Factory::create($this->object_id, $class, $default_class);
        }

        return $this->object;
    }
}
