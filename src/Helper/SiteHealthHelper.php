<?php

namespace Metabolism\WordpressBundle\Helper;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SiteHealthHelper {

	private $status = [
		'pages' => [],
		'ip'    => '',
		'env'   => '',
		'debug' => false,
		'has_error' => false,
		'title' => '',
		'language' => ''
	];

	private $base_url;
	private $output;
	private $full;
	private $password;

	public function __construct(){

		$this->base_url = get_home_url();
		$this->output   = $_REQUEST['output'] ?? false;
		$this->full     = $_REQUEST['full'] ?? false;
		$this->password = $_SERVER['APP_PASSWORD'] ?? false;

		$this->status['title']    = get_bloginfo('name');
		$this->status['language'] = get_bloginfo('language');

		$this->status['ip']    = $this->getIP();
		$this->status['env']   = WP_ENV;
		$this->status['debug'] = WP_DEBUG;
	}

	public function getIP() {

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) )
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else
			$ip = $_SERVER['REMOTE_ADDR'];

		return $ip;
	}

	public function check(){

		$this->checkPosts();
		$this->checkTaxonomies();
		$this->checkPagesWithState();

		if( !$this->output ){
			$content = $this->status['has_error'] ? '0' : '1';
			$response = new Response($content);
		}
		else{
			if( $this->output == 'json' ){
				$content  = $this->status;
				$response = new JsonResponse($content);
			}
			else{
				$content  = $this->_toHTML();
				$response = new Response($content);
			}
		}

		$response->setSharedMaxAge(0);

		return $response;
	}

	private function _toHTML(){

		$html = '<html>';
		$html .= '<head><meta name="viewport" content="width=device-width, initial-scale=1">';
		$html .= '<title>Site Health</title>';
		$html .= '<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700&display=swap" rel="stylesheet">';
		$html .= '<link rel="stylesheet" href="https://unpkg.com/purecss@1.0.1/build/pure-min.css" crossorigin="anonymous">';
		$html .= '<style type="text/css">body{ padding: 20px; font-family: \'Open Sans\', sans-serif } table{ font-size: 14px }</style>';
		$html .= '</head>';
		$html .= '<body>';

		$html .= '<table class="pure-table pure-table-striped">';
		$html .= '<thead><tr><th>Title</th><th>Language</th><th>IP</th><th>Env</th><th>Debug</th></tr></thead>';
		$html .= '<tr><td>'.$this->status['title'].'</td><td>'.$this->status['language'].'</td><td>'.$this->status['ip'].'</td><td>'.$this->status['env'].'</td><td>'.($this->status['debug']?'yes':'no').'</td></tr>';
		$html .= '<table>';

		$html .= '<br>';

		$html .= '<table class="pure-table pure-table-striped" style="width:100%">';
		$html .= '<thead><tr><th>Label</th><th>Url</th><th style="text-align:center">Code</th><th style="text-align:center">Empty</th><th style="text-align:center">Body</th><th style="text-align:center">Timing</th></tr></thead>';

		foreach ( $this->status['pages'] as $page)
			$html .= '<tr><td>'.$page['label'].'</td><td><a href="'.$this->base_url.$page['url'].'" target="_blank">'.$page['url'].'</a></td><td style="text-align:center;color:'.($page['code']!=200?'red':'').'">'.$page['code'].'</td><td style="text-align:center">'.($page['empty']?'yes':'no').'</td><td style="text-align:center">'.($page['body']>0?'yes':'no').'</td><td style="text-align:center">'.$page['response_time'].'ms</td></tr>';

		$html .= '<table>';

		$html .= '</body></html>';

		return $html;
	}

	private function getStatus($label, $url=''){

		if( is_wp_error($url) )
			return;

		$time_start = microtime(true);
		$response   = wp_remote_get($this->base_url.$url.($this->password?'?APP_PASSWORD='.$this->password:''), ['timeout'=>30]);
		$time_end   = microtime(true);

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_body    = wp_remote_retrieve_body( $response );

		$page = [
			'label'         => $label,
			'url'           => $url,
			'code'          => $response_code,
			'response_time' => round($time_end*1000-$time_start*1000),
			'empty'         => empty($response_body),
			'body'          => strpos($response_body, '</body>')>0
		];

		$page['valid'] = $page['code']==200 && !$page['empty'] && $page['body']>0;

		$this->status['has_error'] = $this->status['has_error'] || !$page['valid'];
		$this->status['pages'][] = $page;
	}

	private function checkPosts(){

		global $wp_post_types;

		$home = get_option('page_on_front');

		foreach ($wp_post_types as $post_type)
		{
			if( $post_type->public && ($post_type->publicly_queryable || $post_type->name == 'page') && $post_type->name != 'attachment'){

				$posts = get_posts(['post_type'=>$post_type->name, 'exclude'=>$home, 'posts_per_page'=>($this->full?-1:1)]);

				foreach ($posts as $post){

					$url = get_permalink($post);
					$this->getStatus('Post '.$post_type->name, $url);
				}

				if( $post_type->has_archive ){

					$url = get_post_type_archive_link($post_type->name);
					$this->getStatus('Post '.$post_type->name.' archive', $url);
				}
			}
		}
	}

	private function checkPagesWithState(){

		$options = wp_load_alloptions();

		foreach ($options as $option=>$value){

            if( strpos($option, 'page_on_') !== 0 )
                continue;

			$page = str_replace('page_on_', '', $value);

			$url = get_page_link($page);
			$this->getStatus('State '.$page, $url);
		}

		$this->getStatus( 'Home', '/' );
	}

	private function checkTaxonomies(){

		global $wp_taxonomies;

		foreach ($wp_taxonomies as $taxonomy){

			//todo: better category handle
			if( $taxonomy->public && $taxonomy->publicly_queryable && !in_array($taxonomy->name, ['post_tag','post_format','category']) ){

				$terms = get_terms(['taxonomy'=>$taxonomy->name, 'number'=>($this->full?0:1)]);

				foreach ($terms as $term){

					$url = get_term_link($term, $taxonomy->name);
					$this->getStatus('Taxonomy '.$taxonomy->name, $url);
				}
			}
		}
	}
}
