<?php

namespace Metabolism\WordpressBundle\Entity;

use Metabolism\WordpressBundle\Factory\BlockFactory;
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

    protected $post;

    protected $name;

    protected $block;

    protected $inner_blocks;
    protected $inner_blocks_list;

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
     * @return bool
     */
    public function exist(){

        return str_starts_with( $this->ID, 'block_' );
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
    public function getInnerBlocks(){

        if( is_null($this->inner_blocks) ){

            $blocks = $blocks_list = [];

            foreach ($this->block['innerBlocks'] as $_block){

                if( !empty($_block['blockName']) ){

                    $blocks[] = BlockFactory::create($_block);
                    $blocks_list[] = $_block['blockName'];
                }
            }

            $this->inner_blocks = $blocks;
            $this->inner_blocks_list = array_unique($blocks_list);
        }

        return $this->inner_blocks;
    }

    /**
     * @return bool
     */
    public function hasInnerBlock($name){

        if( is_null($this->inner_blocks_list) )
            $this->getInnerBlocks();

        return in_array($name, $this->inner_blocks_list);
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
    private function get($_block){

        $block = $_block;

        if( class_exists('ACF') && !empty($block['attrs']) ){

            $attrs = $block['attrs'];
            $attrs['id'] = acf_ensure_block_id_prefix(acf_get_block_id( $attrs ));

            if( $block = acf_prepare_block($attrs) ){

                $block['blockName'] = $block['name'];
                $block['innerBlocks'] = $_block['innerBlocks']??[];

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
            return $this->render();
    }

    /**
     * @return string|ACFHelper
     */
    public function getProps(){

        return $this->custom_fields;
    }

    /**
     * @return Post
     * @throws \Exception
     */
    public function getPost(){

        if( is_null($this->post) ){

            $postRepository = new PostRepository();
            $this->post = $postRepository->findQueried(true);
        }

        return $this->post;
    }

    /**
     * @return string
     */
    public function render($is_preview=false){

        $twig = TwigHelper::getEnvironment();

        try {

            $template = $twig->load($this->block['render_template']);

        } catch (\Throwable $t) {

            return $t->getMessage();
        }

        $blog = Blog::getInstance();

        try {

            $post = $this->getPost();

            $props = apply_filters('render_block_content', $this->getProps(), $this);

            $html = $template->render([
                'props'=>$props,
                'post'=>$post,
                'block'=>$this,
                'blog'=>$blog,
                'is_preview'=>$is_preview,
                'is_admin'=>is_admin(),
                'is_front_page'=>is_front_page()
            ]);

            return apply_filters('render_block_template', $html, $props, $this);

        } catch (\Throwable $t) {

            return $t->getMessage();
        }
    }
}
