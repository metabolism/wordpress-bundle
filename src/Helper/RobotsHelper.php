<?php

namespace Metabolism\WordpressBundle\Helper;

use Symfony\Component\HttpFoundation\Response;

class RobotsHelper {

    public function is_bot() {

        $system = $_SERVER['HTTP_USER_AGENT']??'';
        $bot_list = ['Googlebot', 'Baiduspider', 'bingbot', 'Yahoo! Slurp', 'msnbot'];

        foreach($bot_list as $bl) {

            if( stripos( $system, $bl ) !== false )
                return true;
        }

        return false;
    }

	public function doAction(){

		ob_start();
		@do_action( 'do_robots' );
		$content = ob_get_contents();
		ob_end_clean();

		$site_url = parse_url( site_url() );
		$path     = ( ! empty( $site_url['path'] ) ) ? $site_url['path'] : '';

        if( $this->is_bot() ){

            $content = str_replace("Disallow: $path/wp-admin/", "Disallow: $path", $content);
            $content = str_replace("Allow: $path/wp-admin/admin-ajax.php\n", "Disallow: /wp-bundle\n", $content);
        }
        else{

            $content = str_replace("Disallow: $path/wp-admin/", "", $content);
            $content = str_replace("Allow: $path/wp-admin/admin-ajax.php\n", "", $content);
        }

		$response = new Response($content);
		$response->headers->set('Content-Type', 'text/plain; charset=utf-8');

		return $response;
	}
}
