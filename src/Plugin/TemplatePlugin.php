<?php

namespace Metabolism\WordpressBundle\Plugin{

	class TemplatePlugin {

		/**
		 * The array of templates that this plugin tracks.
		 */
		protected $config;

		private $post_states;

		/**
		 * Add template to page/post selector
		 */
		public function addTemplates() {

			$templates = $this->config->get('template', []);

			// Add a filter to the wp 4.7 version attributes metabox
			foreach ($templates as $post_type=>$templates)
			{
				add_filter( 'theme_'.$post_type.'_templates', function($post_templates) use($templates){

					return array_merge($post_templates, $templates);
				});
			}
		}


		/**
		 * Save post state
		 */
		public function getPostStates() {

			$post_states = $this->config->get('page_states', []);

			foreach ($post_states as $post_state=>$label){

				if( isset($_POST['page_on_'.$post_state]) ){

					update_option( 'page_on_'.$post_state, $_POST['page_on_'.$post_state] );
					$this->post_states[$_POST['page_on_'.$post_state]] = $label;
				}
				else{
					$this->post_states[get_option( 'page_on_'.$post_state)] = $label;
				}
			}
		}


		/**
		 * Display post state
		 */
		public function addPostState($post_states, $post) {

			if( in_array($post->ID, array_keys($this->post_states)) ) {
				$post_states[] = $this->post_states[$post->ID];
			}

			return $post_states;
		}


		/**
		 * Add reading options
		 */
		public function addReadingOptions() {

			$post_states = $this->config->get('page_states', []);

			if( empty($post_states) )
				return;

			add_settings_field('page_states', __('Page states'), function() use($post_states){

				foreach ($post_states as $post_state=>$label){

					printf(
						__( $label.' : %s <br/><br/>' ),
						wp_dropdown_pages(
							array(
								'name'              => 'page_on_'.$post_state,
								'echo'              => 0,
								'show_option_none'  => __( '&mdash; Select &mdash;' ),
								'option_none_value' => '0',
								'selected'          => get_option( 'page_on_'.$post_state ),
							)
						)
					);
				}

			}, 'reading');
		}

		/**
		 * Initializes the plugin by setting filters and administration functions.
		 */
		public function __construct($config) {

			if( !is_admin() )
				return;

			$this->config = $config;

			$this->addTemplates();
			$this->getPostStates();

			add_filter( 'display_post_states', [$this, 'addPostState'], 10, 2 );
			add_action( 'admin_init', [$this, 'addReadingOptions'] );
		}
	}
}



namespace {

	function get_page_by_state($state, $output = OBJECT)
	{
		if( !is_string($state) )
			return false;
		
		$page = get_option('page_on_'.$state);

		if( $page )
			return get_post( $page, $output );

		return false;
	}
}
