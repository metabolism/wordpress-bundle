<?php

/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

/**
 * Trait TemplateEngineTrait
 *
 * Provide a set of methods which can be used in template engine ( smarty, twig, ...)
 *
 */

namespace Metabolism\WordpressLoader\Traits;

trait TemplateEngineTrait {

    private $base_path;

    /**
     * TemplateEngineTrait constructor.
     *
     * @param $base_path
     */
    public function __construct($base_path)
    {
        $this->base_path = $base_path;
    }


    public function striptag($string, $tag)
    {
	    $tag = str_replace('<', '', str_replace('>', '', $tag ));
	    return preg_replace("/<\\/?" . $tag . "(.|\\s)*?>/",'', $string);
    }


    public function implode($pieces, $glue = ",", $key = false)
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

        switch ( $type_test ) {

            default:
                return false;
                break;

            case 'array':
                return is_array( $var );
                break;

            case 'bool':
                return is_bool( $var );
                break;

            case 'float':
                return is_float( $var );
                break;

            case 'int':
                return is_int( $var );
                break;

            case 'numeric':
                return is_numeric( $var );
                break;

            case 'object':
                if ( !is_array($var) ) return false;
                return array_keys($var) !== range(0, count($var) - 1);
                break;

            case 'scalar':
                return is_scalar( $var );
                break;

            case 'string':
                return is_string( $var );
                break;
            case 'datetime':
                return ( $var instanceof \DateTime );
                break;
        }
    }


    /**
     * Email string verification.
     *
     * @param        $text
     * @param string $mailto
     * @return mixed
     */
    public function protect_email($text, $mailto = '@')
    {

        preg_match_all( '/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6})/', $text, $potentialEmails, PREG_SET_ORDER );

        $potentialEmailsCount = count( $potentialEmails );

        for ( $i = 0; $i < $potentialEmailsCount; $i++ ) {

            if ( filter_var( $potentialEmails[$i][0], FILTER_VALIDATE_EMAIL ) ) {

                $email = $potentialEmails[$i][0];
                $email = explode( '@', $email );

                $text = str_replace( $potentialEmails[$i][0], '<a data-name="' . $email[0] . '" data-domain="' . $email[1] . '">' . $mailto . '</a>', $text );
            }
        }

        return $text;
    }


    /**
     * Returns the video ID of a youtube video.
     *
     * @param $url
     * @return string
     */
    public function youtube_id($url)
    {

        preg_match( "/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/", $url, $matches );

        return count( $matches ) > 1 ? $matches[1] : '';

    }


    /**
     * @return string
     */
    public function getName()
    {

        return 'rocket';
    }


    /**
     * format id
     *
     * @param $text
     * @return string
     */
    public function clean_id($text)
    {
        return ucfirst( str_replace( '/', ' - ', trim( trim( preg_replace( '/_|-/', ' ', $text ), '/' ) ) ) );
    }


    /**
     * @param $locale
     * @return string
     */
    public function ll_CC($locale)
    {
        //todo: need check
        return $locale . '_' . strtoupper( $locale );
    }

    /**
     * @param $text
     * @return mixed
     */
    public function br_to_space($text)
    {

        return preg_replace( '/\s+/', ' ', str_replace( '<br>', ' ', str_replace( '<br/>', ' ', str_replace( '<br />', ' ', $text ) ) ) );
    }

	/**
	 * @param $objects
	 * @param $attrs
	 * @return mixed
	 * @internal param $text
	 */
    public function bind($objects, $attrs)
    {
	    $binded_objects = [];
	    $objects = (array)$objects;

        foreach ($objects as $object){

	        $object = (array)$object;

	        if( is_array($attrs) ){

		        $binded_object = [];
		        foreach ($attrs as $dest=>$source){

			        $binded_object[$dest] = isset($object[$source]) ? $object[$source] : false;
		        }

		        $binded_objects[] = $binded_object;
	        }
	        else{

		        $binded_objects[] = isset($object[$attrs]) ? $object[$attrs] : false;
	        }
        }

        return $binded_objects;
    }

    /**
     * @param $text
     * @return mixed
     */
    public function clean_space($text)
    {

        return preg_replace( '/\s+/', ' ', str_replace( '<br>', ' <br/>', str_replace( '<br/>', ' <br/>', str_replace( '<br />', ' <br/>', $text ) ) ) );
    }

	/**
	 * @param $image_url
	 * @return mixed
	 */
    public function get_dimensions($image_url){

	    $file = str_replace($this->base_path, BASE_URI.'/web/', $image_url);

	    if( file_exists($file) )
		    return getimagesize($file);
	    else
	    	return false;
    }


	/**
	 * @param $image_url
	 * @return mixed
	 */
    public function get_width($image_url)
    {
	    $sizes = $this->get_dimensions($image_url);
	    if( $sizes and is_array($sizes) )
	    	return $sizes[0];
	    else
	    	return '';
    }

	/**
	 * @param $image_url
	 * @return mixed
	 */
    public function get_height($image_url)
    {
	    $sizes = $this->get_dimensions($image_url);
	    if( $sizes and is_array($sizes) )
		    return $sizes[1];
	    else
		    return '';
    }

    /**
     * @param $text
     * @return mixed
     */
    public function more($text, $more='Lire la suite')
    {
	    return str_replace('<p><!--more--></p>', '<div class="more"><a>'.$more.'</a></div><div class="is-more">', $text).'</div>';
    }

    /**
     * @param        $text
     * @param string $charset
     * @return mixed|string
     */
    public function remove_accent($text, $charset = 'utf-8')
    {

        $str = htmlentities( $text, ENT_NOQUOTES, $charset );

        $str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
        $str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str ); // pour les ligatures e.g. '&oelig;'
        $str = preg_replace( '#&[^;]+;#', '', $str ); // supprime les autres caractères

        return $str;
    }

    public function upload_url($file)
    {

        return $this->base_path . '/upload' . $file;
    }

    public function asset_url($file, $version=false)
    {
        if( $version )
            $file .= (strpos($file, '?' ) !== false ? '&v=' : '?v=' ).$version;

        return $this->base_path . '/static' . $file;
    }

    public function GT($reference, $compare)
    {

        return $reference > $compare;
    }

    public function GTE($reference, $compare)
    {

        return $reference >= $compare;
    }

    public function LT($reference, $compare)
    {

        return $reference < $compare;
    }

    public function LTE($reference, $compare)
    {

        return $reference <= $compare;
    }

    public function blank()
    {

        return $this->base_path . '/static/media/blank.png';
    }

    public function sizer($sizer, $img_tag = true)
    {

        $sizer = str_replace( '/', 'x', $sizer );

        if ( $img_tag ) {
            return '<img src="' . $this->base_path . '/static/media/sizer/' . $sizer . '.png" class="ux-sizer">';
        }
        else {
            return $this->base_path . '/static/media/sizer/' . $sizer . '.png';
        }
    }

    /**
     * @param $key
     * @param $array
     * @param $increment
     * @return bool
     */
    public function adjacent_key($key, $array, $increment = 1)
    {
        $keys        = array_keys( $array );
        $found_index = array_search( $key, $keys );

        if ( $found_index === false ) {
            return false;
        }

        return isset( $keys[$found_index + $increment] ) ? $keys[$found_index + $increment] : false;
    }

    public function translate($text)
    {

        //todo
        return $text;
    }

}
