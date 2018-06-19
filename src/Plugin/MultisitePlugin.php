<?php

namespace Metabolism\WordpressBundle\Plugin;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class MultisitePlugin {


	public function setup()
	{
		add_filter( 'msls_admin_icon_get_edit_new', function($path){

			global $current_blog, $wp_query;
			$current_id = isset($_GET['post'])?$_GET['post']:get_the_ID();

			if( $current_id )
				return $path.'&clone=true&blog_id='.$current_blog->blog_id.'&post_id='.$current_id;
			else
				return $path;
		});


		add_action( 'load-post-new.php', function(){

			if( isset($_GET['blog_id'], $_GET['post_id'], $_GET['clone']))
			{
				switch_to_blog($_GET['blog_id']); // switch to target blog

				$post = get_post($_GET['post_id'], ARRAY_A); // get the original post

				if( !is_wp_error($post) )
				{
					$meta = get_post_meta($_GET['post_id']);

					$post['ID'] = ''; // empty id field, to tell wordpress that this will be a new post

					restore_current_blog(); // return to original blog

					$inserted_post_id = wp_insert_post($post); // insert the post

					foreach($meta as $key=>$value)
						update_post_meta($inserted_post_id,$key,$value[0]);

					restore_current_blog(); // return to original blog

					wp_redirect( get_admin_url( // return to edit page
						get_current_blog_id(),
						'post.php?post='.$inserted_post_id.'&action=edit'
					));

					die();
				}
			}
		});
	}


	public function __construct($config)
	{
		if( is_admin() && $config->get('multisite.clone_post') )
			$this->setup();
	}
}
