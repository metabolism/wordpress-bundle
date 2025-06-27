<?php

namespace Metabolism\WordpressBundle\Factory;

use Metabolism\WordpressBundle\Entity\Post;

class BlogFactory {

	/**
	 * Create entity from post_type
	 * @param null $id
	 * @param bool $post_type
	 * @return bool|Post|\WP_Error
	 */
	public static function create($id=null){

        if( is_null($id) )
            $id = get_current_blog_id();

        return Factory::create($id, 'blog');
    }

}
