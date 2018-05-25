<?php

if( ! class_exists('acf_field_hidden') ) :

	class acf_field_hidden extends acf_field {


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
			$this->name = 'hidden';
			$this->label = __("Hidden",'acf');
			$this->defaults = array(
				'default_value'	=> ''
			);

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

			// default_value
			acf_render_field_setting( $field, array(
				'label'			=> __('Value','acf'),
				'instructions'	=> __('Appears when creating a new post','acf'),
				'type'			=> 'text',
				'name'			=> 'default_value',
			));

		}

	}

endif; // class_exists check
