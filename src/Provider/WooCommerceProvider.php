<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Metabolism\WordpressBundle\Provider;

use Metabolism\WordpressBundle\Entity\Post;
use Metabolism\WordpressBundle\Traits\SingletonTrait;


/**
 * Class WooCommerceProvider
 *
 * @package Metabolism\WordpressBundle\Provider
 */
class WooCommerceProvider
{
    use SingletonTrait;


    /**
     * @param $context
     */
    public function globalContext(&$context)
    {

        // WooCommerce Notices
        $context['wc_notices'] = wc_get_notices();
        wc_clear_notices();

    }

    /**
     * Add current product to TemplateEngine context
     *
     * @param $context
     * @return bool whether or not context is modified.
     */
    public function singleProductContext(&$context)
    {
        if (is_singular('product')) {

            $context['post']    = PostFactory::create($context['post']->ID);
            $product            = wc_get_product( $context['post']->ID );
            $context['product'] = $product;

            return true;
        }
        return false;
    }


    /**
     * Add categories to TemplateEngine context
     *
     * @param $context
     * @return bool whether or not context is modified.
     */
    public function productCategoryContext(&$context)
    {

        if ( is_product_category() ) {
            $queried_object = get_queried_object();
            $term_id = $queried_object->term_id;
            $context['category'] = get_term( $term_id, 'product_cat' );
            $context['title'] = single_term_title('', false);

            return true;
        }
        return false;
    }


    /** Add cart content to TemplateEngine context */
    public function cartContext(&$context)
    {

        /** @var array $kept_products Products in your basket */
        $context['cart'] = [];

        $cart = WC();
        foreach ( WC()->cart->get_cart_for_session() as $cart_item_key => $cart_item )
        {
            $product = [];
            $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
            $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

            if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) )
            {
                $product_permalink  = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
                $product_classes    = esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) );
                $product_remove_url = esc_url( WC()->cart->get_remove_url( $cart_item_key ) );
            }

            array_push($context['cart'], $product);
        }

        return $context;
    }


    /**
     * Add products to TemplateEngine context
     * @param $context
     * @return bool whether or not context is modified.
     */
    public function productsContext(&$context)
    {
        $posts = Timber::get_posts();
        if ($posts === null || $posts === false) {

            return false;
        }

        $context['products'] = $posts;

        return true;
    }

    public function accountContext(&$context)
    {

        return $context;
    }

}
