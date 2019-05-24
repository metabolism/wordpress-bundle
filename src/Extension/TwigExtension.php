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
			new TwigFunction( "__", [$this,'translate'] ),
			new TwigFunction( "fn", [$this,'execFunction'] ),
			new TwigFunction( "function", [$this,'execFunction'] ),
			new TwigFunction( "shortcode", "shortcode" ),
			new TwigFunction( "archive_url", "get_post_type_archive_link" ),
			new TwigFunction( "post_url", "get_permalink" ),
			new TwigFunction( "term_url", "get_term_link" ),
			new TwigFunction( "bloginfo", "bloginfo" )
		];
	}

	public function execFunction( $function_name )
	{
		$args = func_get_args();

		array_shift($args);

		if ( is_string($function_name) )
			$function_name = trim($function_name);

		return call_user_func_array($function_name, ($args));
	}


	/**
	 * @param $text
	 * @return mixed
	 */
	public function more($text, $cta='Lire la suite')
	{
		return str_replace('<p><!--more--></p>', '<more cta="'.$cta.'">'.$text.'</more>', $text);
	}


	/**
	 * @param $text
	 * @return mixed
	 */
	public function translate($text)
	{
		return $text;
	}
}
