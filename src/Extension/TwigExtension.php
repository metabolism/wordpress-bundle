<?php

/**
 * Class TwigExtension
 *
 * Provide a set of methods which can be used in template engine
 *
 */

namespace Metabolism\WordpressBundle\Extension;

use Metabolism\WordpressBundle\Entity\Image;
use Twig\Extension\AbstractExtension,
	Twig\TwigFilter,
	Twig\TwigFunction;

class TwigExtension extends AbstractExtension{

	public function getFilters()
	{
		return [
			new TwigFilter( 'placeholder', [$this, 'placeholder'] )
		];
	}

	/**
	 * @return array
	 */
	public function getFunctions()
	{
		return [
			new TwigFunction( 'fn', [$this, 'execFunction'] ),
			new TwigFunction( 'function', [$this, 'execFunction'] ),
			new TwigFunction( 'shortcode', 'shortcode' ),
			new TwigFunction( 'archive_url', 'get_post_type_archive_link' ),
			new TwigFunction( 'post_url', [$this, 'getPermalink'] ),
			new TwigFunction( 'term_url', [$this, 'getTermLink'] ),
			new TwigFunction( 'bloginfo', 'bloginfo' )
		];
	}


	/**
	 * @param object|int|string $term
	 * @param string $taxonomy
	 * @return mixed
	 */
	public function getTermLink( $term, $taxonomy = '' )
	{
		$link = get_term_link($term, $taxonomy);

		if( !is_string($link) )
			return false;

		return $link;
	}


	/**
	 * @param $function_name
	 * @return mixed
	 */
	public function execFunction( $function_name )
	{
		$args = func_get_args();

		array_shift($args);

		if ( is_string($function_name) )
			$function_name = trim($function_name);

		return call_user_func_array($function_name, ($args));
	}


	/**
	 * @param $page
	 * @param bool $by
	 * @return mixed
	 */
	public function getPermalink( $page, $by=false )
	{
		switch ( $by ){

			case 'state':

				$page = get_page_by_state($page);
				break;

			case 'path':

				$page = get_page_by_path($page);
				break;

			case 'title':

				$page = get_page_by_title($page);
				break;
		}

		if( $page ){

			$link = get_permalink($page);

			if( !is_string($link) )
				return false;

			return $link;
		}
		else
			return false;
	}


	/**
	 * @param $image
	 * @param bool $params
	 * @return Image
	 */
	public function placeholder($image, $params=false)
	{
		if( !$image || !$image instanceof Image )
			return new Image();

		return $image;
	}
}
