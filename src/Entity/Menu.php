<?php

namespace Metabolism\WordpressBundle\Entity;


/**
 * Class Menu
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Menu extends Entity
{
	public $entity = 'menu';

	/** @var MenuItem[] $items */
	protected $items;
	protected $slug;
	protected $title;
    protected $itemClass;
    protected $menu;

    public static $locations;

    public function __toString(): string
    {
        return $this->title??'';
    }

    /**
     * Menu constructor.
     * @param int $id
     */
	public function __construct($id) {

		$app_classname = 'App\Entity\MenuItem';

		if( class_exists($app_classname) )
			$this->itemClass = $app_classname;
		else
			$this->itemClass = 'Metabolism\WordpressBundle\Entity\MenuItem';

        if( is_string($id) )
            $id = $this->getMenuIdFromLocations($id);

		if ( $id && $menu = $this->get($id) ){

            $this->menu = $menu;
            $this->ID = $id;
            $this->title = $menu->name;
            $this->slug = $menu->slug;

            $this->loadMetafields($this->ID, 'term');
        }
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
	 * @return false|MenuItem[]
	 */
	public function getItems(){

		if( is_null($this->items) ){

			$menu_items = wp_get_nav_menu_items($this->ID);

			if ( !$menu_items ){

				$this->items = false;
				return $this->items;
			}

			_wp_menu_item_classes_by_context($menu_items);

			foreach ($menu_items as $item)
				$this->items[] = new $this->itemClass($item);

			$this->items = $this->addDepth();
		}

		return $this->items;
	}


	/**
	 * @param $menu_id
	 * @return false|\WP_Term
	 */
	protected function get($menu_id )
	{
        return wp_get_nav_menu_object($menu_id);
	}


	/**
	 * @param int $parent_id
	 * @return array
	 */
	protected function addDepth($parent_id=0)
	{
		$branch = [];

		foreach ($this->items as $item)
		{
			if( $item->getItemParent() == $parent_id )
			{
				if( $children = $this->addDepth($item->ID))
					$item->setChildren($children);

				$branch[] = $item;
			}
		}

		return $branch;
	}


	/**
	 * @internal
	 * @param string $slug
	 * @return false|integer
	 */
	protected function getMenuIdFromLocations( $slug )
	{
        if( is_null(self::$locations) )
            self::$locations = get_nav_menu_locations();

        if ( is_array(self::$locations) && count(self::$locations) && $menu_id = (self::$locations[$slug]??false) ) {

			if ( function_exists('wpml_object_id_filter') )
				$menu_id = wpml_object_id_filter($menu_id, 'nav_menu');

			return $menu_id;
		}

		return false;
	}
}
