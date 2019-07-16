<?php

namespace Metabolism\WordpressBundle\Helper;

use Symfony\Component\HttpFoundation\Response;

class SiteHealth {

	private $status = [];
	private $has_error = false;
	private $base_url = '';
	private $output = false;

	public function __construct(){

		$this->base_url = get_home_url();
		$this->output = $_REQUEST['output']??false;
	}

	public function check(){

		$this->getStatus( 'Home', '/' );

		$this->checkPosts();
		$this->checkTaxonomies();
		$this->checkPages();
		
		if( !$this->output ){
			$output = $this->has_error ? '0' : '1';
		}
		else{
			$output = $this->_toHTML();
		}

		$response = new Response($output, $this->has_error ? 406 : 200);
		return $response;
	}

	private function _toHTML(){

		$html = '<html>';
		$html .= '<head><meta name="viewport" content="width=device-width, initial-scale=1">';
		$html .= '<title>Site Health</title>';
		$html .= '<link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">';
		$html .= '<link rel="stylesheet" href="https://unpkg.com/purecss@1.0.1/build/pure-min.css" crossorigin="anonymous">';
		$html .= '<style type="text/css">body{ padding: 20px; font-family: Roboto, sans-serif }</style>';
		$html .= '</head>';
		$html .= '<body><table class="pure-table pure-table-striped" style="width:100%">';
		$html .= '<thead><tr><th>Label</th><th>Url</th><th style="text-align:center">Code</th><th style="text-align:center">Empty?</th></tr></thead>';

		$i=0;
		foreach ( $this->status as $status){

			$html .= '<tr><td>'.$status['label'].'</td><td><a href="'.$this->base_url.$status['url'].'" target="_blank">'.$status['url'].'</a></td><td style="text-align:center;color:'.($status['code']!=200?'red':'').'">'.$status['code'].'</td><td style="text-align:center">'.($status['empty']?'yes':'no').'</td></tr>';
			$i++;
		}

		$html .= '<table></body></html>';

		return $html;
	}

	private function getStatus($label, $url=''){

		if( is_wp_error($url) )
			return;

		$response = wp_remote_get($this->base_url.$url);
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		$status = [
			'label'=>$label,
			'url'=>$url,
			'code'=>$response_code,
			'empty'=>empty($response_body)
		];

		$status['valid'] = $status['code']==200 && !$status['empty'];

		$this->has_error = $this->has_error || !$status['valid'];

		$this->status[] = $status;
	}

	private function checkPosts(){

		global $wp_post_types, $wp_rewrite;

		foreach ($wp_post_types as $post_type)
		{
			if( $post_type->public && isset($wp_rewrite->extra_permastructs[$post_type->name]) ){

				$posts = get_posts(['post_type'=>$post_type->name, 'numberposts'=>1]);

				if( count($posts) ){

					$url = get_post_permalink($posts[0]);
					$this->getStatus('Post '.$post_type->name, $url);
				}
				
				if( $post_type->has_archive ){

					$url = get_post_type_archive_link($post_type->name);
					$this->getStatus('Post '.$post_type->name.' archive', $url);
				}
			}
		}
	}

	private function checkPages(){

		global $_config;

		$page_states = $_config->get('page_states', []);

		foreach ($page_states as $state=>$label){

			$page = get_option('page_on_'.$state);

			$url = get_page_link($page);
			$this->getStatus('State '.$state, $url);
		}
	}

	private function checkTaxonomies(){

		global $wp_taxonomies;
		global $wp_rewrite;

		foreach ($wp_taxonomies as $taxonomy){

			if( $taxonomy->public && isset($wp_rewrite->extra_permastructs[$taxonomy->name]) ){

				$terms = get_terms(['taxonomy'=>$taxonomy->name, 'number'=>1]);

				if( count($terms) ){

					$url = get_term_link($terms[0], $taxonomy->name);
					$this->getStatus('Taxonomy '.$taxonomy->name, $url);
				}
			}
		}
	}
}
