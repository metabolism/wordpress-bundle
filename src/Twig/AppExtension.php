<?php

/**
 * Class AppExtension
 *
 * Provide a set of methods which can be used in template engine
 *
 */

namespace Metabolism\WordpressBundle\Twig;

use Twig\Extension\AbstractExtension,
	Twig\TwigFilter,
	Twig\TwigFunction;

class AppExtension extends AbstractExtension{


	/**
	 * @return array|TwigFilter[]
	 */
	public function getFilters()
	{
		return [
			new TwigFilter( 'clean_id', [$this,'cleanID'] ),
			new TwigFilter( 'format_number', [$this,'formatNumber'] ),
			new TwigFilter( 'll_CC', [$this,'llCC'] ),
			new TwigFilter( 'br_to_space', [$this,'brToSpace'] ),
			new TwigFilter( 'remove_accent', [$this,'removeAccent'] ),
			new TwigFilter( 'typeOf', [$this,'typeOf'] ),
			new TwigFilter( 'bind', [$this,'bind'] ),
			new TwigFilter( 'implode', [$this,'implode'] ),
			new TwigFilter( 'striptag', [$this, 'striptag']),
			new TwigFilter( 'br_to_line', [$this, 'brToLine']),
			new TwigFilter( 'remove_br', [$this, 'removeBr']),
			new TwigFilter( 'file_content', [$this, 'getFileContent']),
			new TwigFilter( 'wrap_embed', [$this, 'wrapEmbed']),
			new TwigFilter( 'truncate', [$this, 'truncate'])		];
	}

	/**
	 * @return array|TwigFunction[]
	 */
	public function getFunctions()
	{
		return [
			new TwigFunction( 'blank', [$this,'blank'] )
		];
	}


	/**
	 * @return string
	 */
	public function blank()
	{
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
	}


	/**
	 * @param $path
	 * @return false|string
	 */
	public function getFileContent($path)
	{
		if( file_exists($path) )
			return file_get_contents($path);

		return 'file doesn`t exists';
	}


	/**
	 * @param $content
	 * @return string|string[]|null
	 */
	public function wrapEmbed($content)
	{
		$content = preg_replace( '/<object/Si', '<div class="embed-container"><object', $content );
		$content = preg_replace( '/<\/object>/Si', '</object></div>', $content );

		$content = preg_replace( '/<iframe.+?src=\"(.+?)\"/Si', '<div class="embed-container"><iframe src="\1" frameborder="0" allowfullscreen>', $content );
		return preg_replace( '/<\/iframe>/Si', '</iframe></div>', $content );
	}


	/**
	 * @param $string
	 * @param $tag
	 * @return string|string[]|null
	 */
	public function striptag($string, $tag)
	{
		$tag = str_replace('<', '', str_replace('>', '', $tag ));
		return preg_replace('/<\\/?' . $tag . '(.|\\s)*?>/','', $string);
	}


	/**
	 * @param $string
	 * @return string
	 */
	public function brToLine($string)
	{
		return '<span>'.str_replace('<br/>', '</span><span>', str_replace('<br>', '</span><span>', str_replace('<br />', '</span><span>', $string))).'</span>';
	}


	/**
	 * @param $string
	 * @return string
     */
	public function removeBr($string)
	{
		return str_replace('<br/>', ' ', str_replace('<br>', ' ', str_replace('<br />', ' ', $string)));
	}


	/**
	 * @param $pieces
	 * @param string $glue
	 * @param bool $key
	 * @return string
	 */
	public function implode($pieces, $glue = ',', $key = false)
	{
		if( !$key )
		{
			return implode($glue, $pieces);
		}
		else
		{
			$array = [];
			foreach ($pieces as $piece)
			{
				$piece = (array)$piece;

				if( isset($piece[$key]) )
					$array[] = $piece[$key];
			}

			return implode($glue, $array);
		}
	}


	/**
	 * Template translation of typeof function in PHP
	 *
	 * @see typeof
	 * @param      $var
	 * @param null $type_test
	 * @return bool
	 */
	public function typeOf($var, $type_test = null)
	{
		switch ( $type_test )
		{
			default:
				return false;

			case 'array':
				return is_array( $var );

			case 'bool':
				return is_bool( $var );

			case 'float':
				return is_float( $var );

			case 'int':
				return is_int( $var );

			case 'numeric':
				return is_numeric( $var );

			case 'object':
				if ( !is_array($var) ) return false;
				return array_keys($var) !== range(0, count($var) - 1);

			case 'scalar':
				return is_scalar( $var );

			case 'string':
				return is_string( $var );

			case 'datetime':
				return ( $var instanceof \DateTime );
		}
	}


	/**
	 * format id
	 *
	 * @param $text
	 * @return string
	 */
	public function cleanID($text)
	{
		return ucfirst( str_replace( '/', ' - ', trim( trim( preg_replace( '/_|-/', ' ', $text ), '/' ) ) ) );
	}

	/**
	 * format number with dot as thousands separator
	 *
	 * @param $number
	 * @return string
	 */
	public function formatNumber($number){
		return number_format($number, 0, ',', '.');
	}


	/**
	 * @param $locale
	 * @return string
	 */
	public function llCC($locale)
	{
		return $locale . '_' . strtoupper( $locale );
	}

	/**
	 * @param $text
	 * @return string
	 */
	public function brToSpace($text)
	{
		return preg_replace( '/\s+/', ' ', str_replace( '<br>', ' ', str_replace( '<br/>', ' ', str_replace( '<br />', ' ', $text ) ) ) );
	}

	/**
	 * @param $objects
	 * @param $attrs
	 * @return array
	 * @internal param $text
	 */
	public function bind($objects, $attrs)
	{
		$binded_objects = [];
		$objects = (array)$objects;

		foreach ($objects as $object)
		{
			$object = (array)$object;

			if( is_array($attrs) )
			{
				$binded_object = [];
				foreach ($attrs as $dest=>$source)
				{
					$binded_object[$dest] = $object[$source] ?? false;
				}

				$binded_objects[] = $binded_object;
			}
			else
			{
				$binded_objects[] = $object[$attrs] ?? false;
			}
		}

		return $binded_objects;
	}

	/**
	 * @param $text
	 * @return string
	 */
	public function cleanSpace($text)
	{
		return preg_replace( '/\s+/', ' ', str_replace( '<br>', ' <br/>', str_replace( '<br/>', ' <br/>', str_replace( '<br />', ' <br/>', $text ) ) ) );
	}

	/**
	 * @param        $text
	 * @param string $charset
	 * @return string
	 */
	public function removeAccent($text, $charset = 'utf-8')
	{
		$str = htmlentities( $text, ENT_NOQUOTES, $charset );

		$str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
		$str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str ); // pour les ligatures e.g. '&oelig;'

		return preg_replace( '#&[^;]+;#', '', $str ); // supprime les autres caractères
	}

	/**
	 * @param $string
	 * @param $limit
	 * @param string $ellipsis
	 * @return string
	 */
	public function truncate($string, $limit, $ellipsis=' ...')
	{
		$string = strip_tags($this->brToSpace($string));

		if (strlen($string) > $limit)
		{
			$string = wordwrap($string, intval($limit));
			return substr($string, 0, strpos($string, "\n")).$ellipsis;
		}

		return $string;
	}
}
