<?php

namespace Metabolism\WordpressBundle\Entity;

use Metabolism\WordpressBundle\Helper\ACFHelper;
use Metabolism\WordpressBundle\Helper\TwigHelper;
use Metabolism\WordpressBundle\Repository\PostRepository;

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
		if( $block = $this->get($block) ){

            $this->block = $block;

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

		return $this->block['align']??'full';
	}

	/**
	 * @return mixed
	 */
	public function getAlignText(){

		return $this->block['align_text']??'left';
	}

	/**
	 * @return mixed
	 */
	public function getAlignContent(){

		return $this->block['align_content']??'top';
	}

	/**
	 * @param $block
	 * @return bool|array
	 */
	public function get($block){

		if( empty($block['blockName']??'') )
			return false;

		if( class_exists('ACF') && !empty($block['attrs']) ){

            if( $block = acf_prepare_block($block['attrs']) ){

                if( defined('ACF_MAJOR_VERSION') && ACF_MAJOR_VERSION > 5 )
                    $block['id'] =  acf_ensure_block_id_prefix(acf_get_block_id( $block ));

                $block['blockName'] = $block['name'];

				acf_setup_meta( $block['data']??[], $block['id'], true );

				$this->loadMetafields($block['id'], 'block');

				$this->custom_fields->getFieldObjects();
				$this->custom_fields->setData($block['data']??[]);
			}
		}

		return $block;
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
