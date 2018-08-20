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

	public function __construct( $slug = 0 ) {

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
		$menu = wp_get_nav_menu_items($menu_id);

		if ( $menu )
		{
			_wp_menu_item_classes_by_context($menu);

			$this->orderItems($menu);
			$this->items = $menu;

			$menu_info = wp_get_nav_menu_object($menu_id);

			$this->id = $menu_id;
			$this->title = $menu_info->name;
			$this->slug = $menu_info->slug;
			$this->description = $menu_info->description;
		}
	}


	protected function orderItems(&$menu)
	{
		$ordered_menu = [];

		foreach ($menu as $item)
			$ordered_menu[$item->ID] = Entity::normalize( $item, 'post_');

		foreach ($ordered_menu as $item)
		{
			if( $item['menu_item_parent'] != 0 )
			{
				$ordered_menu[$item['menu_item_parent']]['children'][] = $item;
				unset($ordered_menu[$item['ID']]);
			}
		}

		$menu = array_values($ordered_menu);
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
