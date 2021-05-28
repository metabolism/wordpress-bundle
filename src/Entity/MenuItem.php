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

	public $class;
	public $description;
    public $link;
    public $menu_order;
    /** @var bool $menu_item_parent */
    public $menu_item_parent;
    public $object;
    public $object_id;
    public $target;
	public $title;

    public function __toString()
    {
        return '<a href="'.$this->link.'" target="'.$this->$target.'">'.$this->title.'</a>';
    }

	/**
	 * MenuItem constructor.
	 * @param $data
	 * @param array $args
	 */
	public function __construct($data, $args = [] ) {
		
		if ( $data ){
			$this->import($data, false, 'post_');

			$this->object_id = intval($this->object_id);
			$this->menu_item_parent = intval($this->menu_item_parent);

			unset($this->date, $this->date_gmt, $this->modified, $this->modified_gmt, $this->name);

			if( !isset($args['depth']) || $args['depth'] )
				$this->addCustomFields($this->ID);
		}
	}
}
