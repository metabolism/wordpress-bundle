<?php

namespace Metabolism\WordpressBundle\Entity;


use Metabolism\WordpressBundle\Factory\Factory;

/**
 * Class MenuItem
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class MenuItem extends Entity
{
	public $entity = 'menu-item';

	/** @var MenuItem[] $children */
	protected $children;
	protected $classes;
	protected $class;
	protected $content;
	protected $link;
	protected $order;
	protected $item_parent;
	protected $object_id;
	protected $target;
	protected $title;
	protected $type;
	protected $attr_title;
	protected $current;
	protected $current_item_ancestor;
	protected $current_item_parent;
	protected $object;
	protected $item;

	public function __toString()
	{
		return $this->title ? '<a href="' . $this->link . '" target="' . $this->target . '">' . $this->title . '</a>' : '';
	}

	/**
	 * MenuItem constructor.
	 * @param object $menu_item
	 */
	public function __construct($menu_item)
	{
		if ($menu_item) {

			$this->item = $menu_item;

			$this->ID = $menu_item->ID;
			$this->object_id = intval($menu_item->object_id);
			$this->item_parent = $menu_item->menu_item_parent;
			$this->order = $menu_item->menu_order;
			$this->link = $menu_item->url;
			$this->title = $menu_item->title;
			$this->target = $menu_item->target;
			$this->classes = $menu_item->classes;
			$this->type = $menu_item->type;
			$this->attr_title = $menu_item->attr_title;
			$this->content = $menu_item->post_content;
			$this->current = $menu_item->current;
			$this->current_item_ancestor = $menu_item->current_item_ancestor;
			$this->current_item_parent = $menu_item->current_item_parent;

			$this->loadMetafields($this->ID, 'post');
		}
	}

	/**
	 * @return MenuItem[]
	 */
	public function getChildren(): ?array
	{
		return $this->children;
	}

	/**
	 * @param $children
	 * @return MenuItem
	 */
	public function setChildren($children): self
	{
		$this->children = $children;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getClasses()
	{
		return $this->classes;
	}

	/**
	 * @return string
	 */
	public function getClass(): string
	{
		if( is_null($this->class) )
			$this->class = trim(implode(' ', $this->item->classes));

			return $this->class;
	}

	/**
	 * @param bool|null $nl2br
	 * @return string
	 */
	public function getContent(?bool $nl2br=true): string
	{
		return $nl2br?nl2br($this->content):$this->content;
	}

	/**
	 * @return mixed
	 */
	public function getLink()
	{
		return $this->link;
	}

	/**
	 * @return mixed
	 */
	public function getOrder()
	{
		return $this->order;
	}

	/**
	 * @return mixed
	 */
	public function getItemParent()
	{
		return $this->item_parent;
	}

	/**
	 * @return int
	 */
	public function getObjectId(): int
	{
		return $this->object_id;
	}

	/**
	 * @return mixed
	 */
	public function getTarget()
	{
		return $this->target;
	}

	/**
	 * @return mixed
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @return mixed
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return mixed
	 */
	public function getAttrTitle()
	{
		return $this->attr_title;
	}

	/**
	 * @return mixed
	 */
	public function getCurrent()
	{
		return $this->current;
	}

	/**
	 * @return mixed
	 */
	public function getCurrentItemAncestor()
	{
		return $this->current_item_ancestor;
	}

	/**
	 * @return mixed
	 */
	public function getCurrentItemParent()
	{
		return $this->current_item_parent;
	}

	/**
	 * @return bool|Entity
	 */
	public function getObject()
	{
		if (is_null($this->object)) {

			$default_class = $class = false;

			if ($this->type == 'custom') {

				$this->object = false;
				return $this->object;
			}
			if ($this->type == 'post_type') {

				$default_class = 'post';
				$class = $this->item->object;

			} elseif ($this->type == 'taxonomy') {

				$default_class = 'term';
				$class = $this->item->object;
			}

			$this->object = Factory::create($this->object_id, $class, $default_class);
		}

		return $this->object;
	}
}
