<?php

namespace Metabolism\WordpressBundle\Entity;


/**
 * Class Menu
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class MenuItem extends Entity
{
	/** @var bool $menu_item_parent */
	public $menu_item_parent;

	/** @var [] $classes */
	public $classes;
	public $class;
	public $description;
	public $target;
	public $title;
	public $menu_order;
	public $object_id;
	public $object;
	public $link;

	/** @var MenuItem[] $children */
	public $children;

	/**
	 * MenuItem constructor.
	 * @param $data
	 */
	public function __construct($data ) {
		
		if ( $data ){
			$this->import($data, false, 'post_');

			$this->object_id = intval($this->object_id);
			$this->menu_item_parent = intval($this->menu_item_parent);

			unset($this->date, $this->date_gmt, $this->modified, $this->modified_gmt, $this->name);

			$this->addCustomFields($this->ID);
		}
	}
}
