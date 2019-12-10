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
	public $items;
	public $title;
	public $slug;
	public $description;

	private $menuItemClass;
	private $args;

	/**
	 * Menu constructor.
	 * @param int $slug
	 * @param array $args
	 */
	public function __construct($slug = 0, $args = [] ) {

		$this->args = $args;

		$app_classname = 'App\Entity\MenuItem';

		if( class_exists($app_classname) )
			$this->menuItemClass = $app_classname;
		else
			$this->menuItemClass = 'Metabolism\WordpressBundle\Entity\MenuItem';

		$menu_id = false;
		$locations = get_nav_menu_locations();

		if ( $slug != 0 && is_numeric($slug) )
			$menu_id = $slug;
		else if ( is_array($locations) && count($locations) )
			$menu_id = $this->get_menu_id_from_locations($slug, $locations);
		else if ( $slug === false )
			$menu_id = false;

		if ( $menu_id && $menu = $this->get($menu_id) ){

			if( !isset($args['depth']) || $args['depth'] )
				$this->addCustomFields($menu_id);
		}
	}


	/**
	 * @param $menu_id
	 * @return bool
	 */
	protected function get($menu_id )
	{
		$menu_items = wp_get_nav_menu_items($menu_id);

		if ( !$menu_items )
			return false;

		_wp_menu_item_classes_by_context($menu_items);

		foreach ($menu_items as $item)
			$this->items[] = new $this->menuItemClass($item, $this->args);

		$this->items = $this->addDepth();

		$menu_info = wp_get_nav_menu_object($menu_id);

		$this->ID = $menu_id;
		$this->title = $menu_info->name;
		$this->slug = $menu_info->slug;
		$this->description = $menu_info->description;

		return true;
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
			if( $item->menu_item_parent == $parent_id )
			{
				if( $children = $this->addDepth($item->ID))
					$item->children = $children;

				$branch[] = $item;
			}
		}

		return $branch;
	}


	/**
	 * @internal
	 * @param string $slug
	 * @param array $locations
	 * @return false|integer
	 */
	protected function get_menu_id_from_locations( $slug, $locations )
	{
		if ( isset($locations[$slug]) ) {
			$menu_id = $locations[$slug];
			if ( function_exists('wpml_object_id_filter') ) {
				$menu_id = wpml_object_id_filter($locations[$slug], 'nav_menu');
			}

			return $menu_id;
		}

		return false;
	}
}
