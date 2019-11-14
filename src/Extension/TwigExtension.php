<?php

/**
 * Class TwigExtension
 *
 * Provide a set of methods which can be used in template engine
 *
 */

namespace Metabolism\WordpressBundle\Extension;

use Metabolism\WordpressBundle\Entity\Image;
use Metabolism\WordpressBundle\Factory\Factory;
use Metabolism\WordpressBundle\Factory\PostFactory;
use Metabolism\WordpressBundle\Factory\TaxonomyFactory;

use Twig\Extension\AbstractExtension,
	Twig\TwigFilter,
	Twig\TwigFunction;

class TwigExtension extends AbstractExtension{

	public function getFilters()
	{
		return [
			new TwigFilter( 'placeholder', [$this, 'placeholder'] ),
			new TwigFilter( 'more', [$this, 'more'] )
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
			new TwigFunction( 'login_url', 'wp_login_url' ),
			new TwigFunction( 'search_form', 'get_search_form' ),
			new TwigFunction( 'archive_url', 'get_post_type_archive_link' ),
			new TwigFunction( 'attachment_url', 'wp_get_attachment_ur' ),
			new TwigFunction( 'post_url', [$this, 'getPermalink'] ),
			new TwigFunction( 'term_url', [$this, 'getTermLink'] ),
			new TwigFunction( 'bloginfo', 'bloginfo' ),
			new TwigFunction( 'dynamic_sidebar', function($id){ return $this->getOutput('dynamic_sidebar', [$id]); }, ['is_safe' => array('html')]  ),
			new TwigFunction( 'comment_form', function($post_id, $args=[]){ return $this->getOutput('comment_form', [$args, $post_id]); }, ['is_safe' => array('html')]  ),
			new TwigFunction( 'is_active_sidebar', 'is_active_sidebar' ),
			new TwigFunction( '_e', 'translate' ),
			new TwigFunction( '_x', '_x' ),
			new TwigFunction( '_n', '_n' ),
			new TwigFunction( '__', 'translate' ),
			new TwigFunction( 'wp_head', function(){ return $this->getOutput('wp_head'); }, ['is_safe' => array('html')]  ),
			new TwigFunction( 'wp_footer', function(){ return $this->getOutput('wp_footer'); }, ['is_safe' => array('html')]  ),
			new TwigFunction( 'Post', function($id){ return PostFactory::create($id); } ),
			new TwigFunction( 'User', function($id){ return Factory::create($id, 'user'); } ),
			new TwigFunction( 'Term', function($id){ return TaxonomyFactory::create($id); } ),
			new TwigFunction( 'Image', function($id, $path=false){ return Factory::create($id, 'image', false, ['path'=>$path]); } )
		];
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
	 * @param object|int|string $term
	 * @param string $taxonomy
	 * @return mixed
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
