<?php

/**
 * Class TwigExtension
 *
 * Provide a set of methods which can be used in template engine
 *
 */

namespace Metabolism\WordpressBundle\Extension;

use Twig\Extension\AbstractExtension,
	Twig\TwigFilter,
	Twig\TwigFunction;

class TwigExtension extends AbstractExtension{

	public function getFilters()
	{
		return [
			new TwigFilter( "more", [$this, 'more'] ),
		];
	}

	/**
	 * @return array
	 */
	public function getFunctions()
	{
		return [
			new TwigFunction( "fn", [$this,'execFunction'] ),
			new TwigFunction( "function", [$this,'execFunction'] ),
			new TwigFunction( "shortcode", "shortcode" ),
			new TwigFunction( "archive_url", "get_post_type_archive_link" ),
			new TwigFunction( "post_url", [$this, 'getPermalink'] ),
			new TwigFunction( "term_url", "get_term_link" ),
			new TwigFunction( "bloginfo", "bloginfo" )
		];
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
	 * @param $function_name
	 * @param $by
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

		if( $page )
			return get_permalink($page);
		else
			return false;
	}


	/**
	 * @param $text
	 * @return mixed
	 */
	public function more($text, $cta='Lire la suite')
	{
		return str_replace('<p><!--more--></p>', '<more cta="'.$cta.'">'.$text.'</more>', $text);
	}
}
