<?php

namespace Metabolism\WordpressLoader\Plugin;

class ThumbnailPlugin {

	protected $is_woocommerce_active, $config;

	public function custom_columns( $column ) {

		global $post;

		$post_id = $post->ID;

		switch ( $column ) {

			case 'thumb':

				if ( has_post_thumbnail( $post_id ) ) {

					$thumbnail_id = get_post_thumbnail_id( $post_id );

					if ( !$this->on_woocommerce_products_list() )
					{
						$image = wp_get_attachment_image_src( $thumbnail_id );
						echo '<img src="'.$image[0].'" width="43" height="43" style="display:block">';
					}
				}

				break;
		}
	}

	public function add_thumb_column( $columns ) {
		/**
		 * Check if WooCommerce is active
		 * If so WooCommerce supplies the title for the column and then we bail
		 **/
		if ( $this->on_woocommerce_products_list() ) {
			return $columns;
		} else {
			return array_merge(
				$columns,
				['thumb' => __( 'Thumbnail') ]
			);
		}
	}

	/**
	 * @return bool
	 *
	 * Is WooCommerce installed and activated and we are showing the product post type?
	 *
	 * Logic from here: https://docs.woothemes.com/document/create-a-plugin/
	 */
	public function on_woocommerce_products_list() {
		global $post;
		return $post != null && 'product' == $post->post_type && $this->is_woocommerce_active;
	}


	/**
	 * Initializes the plugin by setting filters and administration functions.
	 */
	public function __construct($config) {

		if( !is_admin() )
			return;

		$this->config = $config;

		add_action( 'admin_init', function(){

			$this->is_woocommerce_active = in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );

			$available_post_types = get_post_types();

			foreach ( $available_post_types as $post_type )
			{
				if( in_array($post_type, $this->config->get('show-thumbnails', []) ) )
				{
					add_action( "manage_{$post_type}_posts_custom_column" ,  [ $this, 'custom_columns' ], 2 );
					add_filter( "manage_{$post_type}_posts_columns" ,        [ $this, 'add_thumb_column' ] );
				}
			}

			// For taxonomies:

			$taxonomies = get_taxonomies( '', 'names' );

			foreach ( $taxonomies as $taxonomy )
			{
				if( in_array($taxonomy, $this->config->get('show-thumbnails', []) ) )
				{
					add_action( "manage_{$taxonomy}_posts_custom_column" ,  [ $this, 'custom_columns' ], 2, 2 );
					add_filter( "manage_{$taxonomy}_posts_columns" ,        [ $this, 'add_thumb_column' ] );
				}
			}
		});
	}
}
