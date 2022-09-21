<?php

namespace Metabolism\WordpressBundle\Entity;

use Metabolism\WordpressBundle\Helper\ACFHelper;
use Metabolism\WordpressBundle\Helper\TwigHelper;
use Metabolism\WordpressBundle\Repository\PostRepository;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class Block
 *
 * @package Metabolism\WordpressBundle\Block
 */
class Block extends Entity
{
	public $entity = 'block';

	protected $name;

	protected $block;

	/**
	 * @param $block
	 */
	public function __construct($block)
	{
		if( $this->get($block) ){

			$this->name = $block['blockName'];
			$this->ID = $block['id']??null;
		}
	}

	/**
	 * @return mixed
	 */
	public function getName(){

		return $this->name;
	}

	/**
	 * @return mixed
	 */
	public function getAlign(){

		return $this->block['attrs']['align']??'full';
	}

	/**
	 * @return mixed
	 */
	public function getAlignText(){

		return $this->block['attrs']['align_text']??'left';
	}

	/**
	 * @return mixed
	 */
	public function getAlignContent(){

		return $this->block['attrs']['align_content']??'top';
	}

	/**
	 * @param $block
	 * @return bool
	 */
	public function get($block){

		if( empty($block['blockName']??'') )
			return false;

		if( class_exists('ACF') && !empty($block['attrs']) ){

			if( $acf_block = acf_get_block_type($block['attrs']['name']) ){

				$this->block = array_merge($block, $acf_block);

				acf_setup_meta( $block['attrs']['data']??[], $block['attrs']['id'], true );

				$this->loadMetafields($block['attrs']['id'], 'block');

				$this->custom_fields->getFieldObjects();
				$this->custom_fields->setData($block['attrs']['data']??[]);
			}
		}
		else{

			$this->block = $block;
		}

		return true;
	}

	/**
	 * @return string|ACFHelper
	 */
	public function getContent(){

		if( !empty($this->block['innerHTML']??'') )
			return $this->block['innerHTML'];
		else
			return $this->custom_fields;
	}

	/**
	 * @return string
	 */
	public function render(){

		$twig = TwigHelper::getEnvironment();

		try {

			$template = $twig->load($this->block['render_template']);

		} catch (\Throwable $t) {

			return $t->getMessage();
		}

		$blog = Blog::getInstance();

		$postRepository = new PostRepository();

		try {

			$post = $postRepository->findQueried();
			return $template->render(['props'=>$this->getContent(), 'post'=>$post, 'block'=>$this, 'blog'=>$blog, 'is_component_preview'=>true]);

		} catch (\Throwable $t) {

			return $t->getMessage();
		}
	}
}
