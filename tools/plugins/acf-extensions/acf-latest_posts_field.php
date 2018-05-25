<?php

if( ! class_exists('acf_field_latest_posts') ) :

	class acf_field_latest_posts extends acf_field {


		/*
		*  initialize
		*
		*  This function will setup the field type data
		*
		*  @type	function
		*  @date	5/03/2014
		*  @since	5.0.0
		*
		*  @param	n/a
		*  @return	n/a
		*/

		function initialize() {

			// vars
			$this->name = 'latest_posts';
			$this->label = __("Latest posts",'acf');
			$this->category = 'relational';
			$this->defaults = array(
				'post_type'	=> '',
				'posts_per_page' => 6
			);
		}


		function format_value( $value, $post_id, $field ) {

			$params = ['post_type', 'posts_per_page'];
			$args = [];

			foreach ($params as $param)
			{
				if( isset($field[$param]) )
					$args[$param] = $field[$param];
			}

			$query = new \WP_Query($args);

			if( !isset($query->posts) || !is_array($query->posts) )
				return [];

			return $query->posts;
		}


		/*
		*  render_field_settings()
		*
		*  Create extra options for your field. This is rendered when editing a field.
		*  The value of $field['name'] can be used (like bellow) to save extra data to the $field
		*
		*  @type	action
		*  @since	3.6
		*  @date	23/01/13
		*
		*  @param	$field	- an array holding all the field's data
		*/

		function render_field_settings( $field ) {

			acf_render_field_setting( $field, array(
				'label'			=> __('Post Type','acf'),
				'instructions'	=> '',
				'type'			=> 'select',
				'name'			=> 'post_type',
				'choices'		=> acf_get_pretty_post_types(),
				'multiple'		=> 0,
				'ui'			=> 0,
				'allow_null'	=> 1,
				'placeholder'	=> __("All post types",'acf'),
			));

			// max
			acf_render_field_setting( $field, array(
				'label'			=> __('Posts per page','acf'),
				'instructions'	=> '',
				'type'			=> 'number',
				'name'			=> 'posts_per_page',
			));

		}

	}

endif; // class_exists check
