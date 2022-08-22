<?php


namespace Metabolism\WordpressBundle\Helper;

use Metabolism\WordpressBundle\Entity\Block;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class BlockHelper
{
	/**
	 * @param $acf_block
	 * @return void
	 */
	public static function render($acf_block){

		$block = [
			'blockName'=>$acf_block['name'],
			'attrs' => $acf_block
		];

		if( $image = $acf_block['data']['_preview_image']??false ){

			echo '<img src="'.get_home_url().'/'.$image.'" style="width:100%;height:auto"/>';
			return;
		}

		$block = new Block($block);

		echo $block->render();
	}

	/**
	 * @param $post_content
	 * @return array
	 */
	public static function parse($post_content){

		$_blocks = parse_blocks($post_content);

		$blocks = [];

		foreach ($_blocks as $_block){

			if( !empty($_block['blockName']) )
				$blocks[] = new Block($_block);
		}

		return $blocks;
	}
}
