<?php

namespace Metabolism\WordpressBundle\Plugin;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class MultisitePlugin {


	public function setup()
	{
		// add blog and post origin
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
				$main_site_id = get_main_network_id();

				// switch to origin blog
				switch_to_blog($_GET['blog_id']);

				// get the original post
				$post = get_post($_GET['post_id'], ARRAY_A);

				if( !is_wp_error($post) )
				{
					// get the original meta
					$meta = get_post_meta($_GET['post_id']);

					// get the original language, fallback to us
					$language = get_option('WPLANG');
					$language = empty($language)?'us':$language;

					// empty id field, to tell wordpress that this will be a new post
					$post['ID'] = '';

					// return to target blog
					restore_current_blog();

					// insert the post
					$inserted_post_id = wp_insert_post($post);

					// register original post
					add_option('msls_'.$inserted_post_id, [$language => $_GET['post_id']], '', 'no');

					$current_site_id = get_current_blog_id();

					// add and filter meta
					foreach($meta as $key=>$value){

						if($key === '_thumbnail_id') {

							if( $current_site_id == $main_site_id )
							{
								switch_to_blog($_GET['blog_id']);
								$original_id = get_post_meta($value[0], '_wp_original_attachment_id', true);
								restore_current_blog();

								update_post_meta($inserted_post_id, $key, $original_id);
							}
							else
							{
								$attachments = get_posts(['numberposts'=>1, 'post_type'=>'attachment', 'meta_value'=>$value[0], 'meta_key'=>'_wp_original_attachment_id', 'fields'=>'ids']);

								if( count($attachments) )
									update_post_meta($inserted_post_id, $key, $attachments[0]);
							}
						}
						else{

							if( function_exists('get_field_object') )
							{
								$field = get_field_object($value[0]);

								if( isset($field['type']) && in_array($field['type'], ['image', 'file']) )
								{
									if( $current_site_id == $main_site_id )
									{
										switch_to_blog($_GET['blog_id']);
										$original_id = get_post_meta($meta[ substr($key, 1) ][0], '_wp_original_attachment_id', true);
										restore_current_blog();

										if( $original_id )
										{
											$meta[ substr($key, 1) ][0] = $original_id;
											update_post_meta($inserted_post_id, substr($key, 1), $original_id);

											continue;
										}
									}
									else
									{
										$attachments = get_posts(['numberposts'=>1, 'post_type'=>'attachment', 'meta_value'=>$meta[ substr($key, 1) ][0], 'meta_key'=>'_wp_original_attachment_id', 'fields'=>'ids']);
										if( count($attachments) )
										{
											$meta[ substr($key, 1) ][0] = $attachments[0];
											update_post_meta($inserted_post_id, substr($key, 1), $attachments[0]);

											continue;
										}
									}
								}
							}

							update_post_meta($inserted_post_id, $key, $value[0]);
						}
					}

					// get the target language, fallback to us
					$language = get_option('WPLANG');
					$language = empty($language)?'us':$language;

					// switch to origin blog
					switch_to_blog($_GET['blog_id']);

					// register new post
					add_option('msls_'.$_GET['post_id'], [$language => $inserted_post_id], '', 'no');

					// return to original blog
					restore_current_blog();

					// return to edit page
					wp_redirect( get_admin_url(get_current_blog_id(), 'post.php?post='.$inserted_post_id.'&action=edit'));

					exit();
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
