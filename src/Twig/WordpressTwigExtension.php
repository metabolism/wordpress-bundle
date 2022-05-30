<?php

/**
 * Class TwigExtension
 *
 * Provide a set of methods which can be used in template engine
 *
 */

namespace Metabolism\WordpressBundle\Twig;

use Metabolism\WordpressBundle\Entity\Image;
use Metabolism\WordpressBundle\Factory\Factory;
use Metabolism\WordpressBundle\Factory\PostFactory;
use Metabolism\WordpressBundle\Factory\TermFactory;

use Twig\Extension\AbstractExtension,
	Twig\TwigFilter,
	Twig\TwigFunction;

class WordpressTwigExtension extends AbstractExtension{

	public function getFilters()
	{
		return [
            new TwigFilter( 'handle', 'sanitize_title' ),
            new TwigFilter( 'placeholder', [$this, 'placeholder'] ),
			new TwigFilter( 'more', [$this, 'more'] ),
			new TwigFilter( 'resize', [$this, 'resize'] ),
			new TwigFilter( 'picture', [$this, 'picture'] ),
			new TwigFilter( 'stripshortcodes','strip_shortcodes' ),
			new TwigFilter( 'function', [$this, 'execFunction'] ),
			new TwigFilter( 'excerpt','wp_trim_words' ),
			new TwigFilter( 'sanitize','sanitize_title' ),
			new TwigFilter( 'shortcodes','do_shortcode' ),
			new TwigFilter( 'wpautop','wpautop' ),
			new TwigFilter( 'array',[$this, 'to_array'] ),
		];
	}

	/**
	 * @return array
	 */
	public function getFunctions()
	{
		return [
			new TwigFunction( 'pixel', [$this, 'generatePixel'] ),
			new TwigFunction( 'fn', [$this, 'execFunction'] ),
			new TwigFunction( 'function', [$this, 'execFunction'] ),
			new TwigFunction( 'action', [$this, 'doAction'] ),
			new TwigFunction( 'shortcode', 'do_shortcode' ),
			new TwigFunction( 'login_url', 'wp_login_url' ),
			new TwigFunction( 'search_form', 'get_search_form' ),
			new TwigFunction( 'archive_url', 'get_post_type_archive_link' ),
			new TwigFunction( 'attachment_url', 'wp_get_attachment_url' ),
			new TwigFunction( 'post_url', [$this, 'getPermalink'] ),
			new TwigFunction( 'term_url', [$this, 'getTermLink'] ),
			new TwigFunction( 'bloginfo', 'get_bloginfo' ),
			new TwigFunction( 'dynamic_sidebar', function($id){ return $this->getOutput('dynamic_sidebar', [$id]); }, ['is_safe' => array('html')]  ),
			new TwigFunction( 'comment_form', function($post_id, $args=[]){ return $this->getOutput('comment_form', [$args, $post_id]); }, ['is_safe' => array('html')]  ),
			new TwigFunction( 'is_active_sidebar', 'is_active_sidebar' ),
			new TwigFunction( '_e', 'translate' ),
			new TwigFunction( '_x', '_x' ),
			new TwigFunction( '_ex', '_ex' ),
			new TwigFunction( '_nx', '_nx' ),
			new TwigFunction( '_n_noop', '_n_noop' ),
			new TwigFunction( '_nx_noop', '_nx_noop' ),
			new TwigFunction( '_n', '_n' ),
			new TwigFunction( '__', 'translate' ),
			new TwigFunction( 'translate', 'translate' ),
			new TwigFunction( 'translate_nooped_plural', 'translate_nooped_plural' ),
			new TwigFunction( 'wp_head', function(){ return @$this->getOutput('wp_head'); }, ['is_safe' => array('html')]  ),
			new TwigFunction( 'wp_footer', function(){ return @$this->getOutput('wp_footer'); }, ['is_safe' => array('html')]  ),
			new TwigFunction( 'Post', function($id){ return PostFactory::create($id); } ),
			new TwigFunction( 'User', function($id){ return Factory::create($id, 'user'); } ),
			new TwigFunction( 'Term', function($id){ return TermFactory::create($id); } ),
			new TwigFunction( 'Image', function($id){ return Factory::create($id, 'image'); } )
		];
	}


	public function to_array( $arr ) {

		return (array)$arr;
	}


    public function generatePixel($w = 1, $h = 1) {

        ob_start();

        $img = imagecreatetruecolor($w, $h);
        imagetruecolortopalette($img, false, 1);
        imagesavealpha($img, true);
        $color = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $color);
        imagepng($img, null, 9);
        imagedestroy($img);

        $imagedata = ob_get_contents();
        ob_end_clean();

       return 'data:image/png;base64,' . base64_encode($imagedata);
    }


    /**
     * Return resized image
     *
     * @param $image
     * @param $width
     * @param int $height
     * @param array $args
     * @return string
     */
    public function resize($image, $width, $height=0, $args=[])
    {
        if( is_string($image) )
            $image = new Image($image);

        if( !$image instanceof Image )
            $image = new Image();

        $args['resize'] = [$width, $height];

        return $image->edit($args);
    }


    /**
     * Return resized picture
     *
     * @param $image
     * @param $width
     * @param int $height
     * @param array $sources
     * @return string
     */
    public function picture($image, $width, $height=0, $sources=[], $alt=false)
    {
        if( !$image instanceof Image )
            $image = new Image();

        return $image->toHTML($width, $height, $sources, $alt);
    }


    /**
     * Return function echo
     * @param $function
     * @param array $args
     * @return string
     */
    private function getOutput($function, $args=[])
    {
        ob_start();
        call_user_func_array($function, $args);
        $data = ob_get_contents();
        ob_end_clean();

        return $data;
    }


	/**
	 * @param object|int|string $term
	 * @param string $taxonomy
	 * @param bool|string $meta
	 * @return false|string
     */
	public function getTermLink( $term, $taxonomy = 'category', $meta=false )
	{

		if( $meta ){

			$args = array(
				'meta_query' => array(
					array(
						'key'       => $meta,
						'value'     => $term,
						'compare'   => 'LIKE'
					)
				),
				'number'  => 1,
				'taxonomy'  => $taxonomy,
			);

			$terms = get_terms( $args );

			if( count($terms) )
				$term = $terms[0];
		}


		$link = get_term_link($term, $taxonomy);

		if( !is_string($link) )
			return false;

		return $link;
	}


	/**
	 * @param $content
	 * @return array
     */
	public function more( $content )
	{
        if ( preg_match( '/<!--more(.*?)?-->/', $content, $matches ) ) {
            if ( has_block( 'more', $content ) ) {
                // Remove the core/more block delimiters. They will be left over after $content is split up.
                $content = preg_replace( '/<!-- \/?wp:more(.*?) -->/', '', $content );
            }

            $content = explode( $matches[0], $content, 2 );
        } else {
            $content = array( $content );
        }

        foreach ($content as &$paragraph)
            $paragraph = force_balance_tags($paragraph);

        return $content;
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
	 * @param $action_name
	 * @param mixed ...$args
	 * @return void
	 */
	public function doAction( $action_name, ...$args )
	{
		do_action_ref_array( $action_name, $args );
	}


	/**
	 * @param $page
	 * @param bool $by
	 * @return false|string
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
		if(!$image instanceof Image)
			return new Image();

		return $image;
	}
}
