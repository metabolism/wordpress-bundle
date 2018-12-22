<?php

namespace Metabolism\WordpressBundle\Entity;


/**
 * Class Menu
 *
 * @package Metabolism\WordpressBundle\Entity
 */
class Menu extends Entity
{
	public $items, $id, $title, $slug, $description;
	private $menuItemClass;

	public function __construct( $slug = 0 ) {

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

		if ( $menu_id )
			$this->get($menu_id);
	}


	protected function get( $menu_id )
	{
		$menu_items = wp_get_nav_menu_items($menu_id);

		if ( !$menu_items )
			return false;

		_wp_menu_item_classes_by_context($menu_items);

		$this->items = $this->orderItems($menu_items);

		$menu_info = wp_get_nav_menu_object($menu_id);

		$this->id = $menu_id;
		$this->title = $menu_info->name;
		$this->slug = $menu_info->slug;
		$this->description = $menu_info->description;

	}


	protected function orderItems($menu_items)
	{
		$ordered_menu = [];

		foreach ($menu_items as $item){

			$ordered_menu[$item->ID] = new $this->menuItemClass($item);
		}

		foreach ($ordered_menu as $item)
		{
			if( $item->menu_item_parent != 0 )
			{
				$ordered_menu[$item->menu_item_parent]->children[] = $item;
				unset($ordered_menu[$item->ID]);
			}
		}

		return array_values($ordered_menu);
	}


	/**
	 * @internal
	 * @param string $slug
	 * @param array $locations
	 * @return integer
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
	}
}
