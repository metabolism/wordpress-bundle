<?php

namespace Metabolism\WordpressBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use function Env\env;

class KernelEventsSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
            RequestEvent::class => 'onKernelRequest',
            ResponseEvent::class => 'onKernelResponse',
        ];
    }
    
    
    /**
     * @param Request $request
     * @return void
     */
    private function fixServerVars(Request $request){
        
        if( empty($_SERVER['REQUEST_METHOD']??'') )
            $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        
        if( empty($_SERVER['REQUEST_URI']??'') )
            $_SERVER['REQUEST_URI'] = $request->getRequestUri();
        
        if( empty($_SERVER['PHP_SELF']??'') )
            $_SERVER['PHP_SELF'] = $request->getScriptName();
        
        if( empty($_SERVER['PATH_INFO']??'') )
            $_SERVER['PATH_INFO'] = $request->getPathInfo();
    }
    
    /**
     * @param ControllerEvent $event
     * @return void
     * @throws \Exception
     */
    public function onKernelController(ControllerEvent $event)
    {
        if ( !$event->isMainRequest() )
            return;
        
        if( wp_is_maintenance_mode() || (function_exists('wp_maintenance_mode') && wp_maintenance_mode()) )
            throw new \Exception('Service Unavailable', 503);
    }
    
    
    /**
     * @param RequestEvent $event
     * @return void
     * @throws \Exception
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if( defined('REST_REQUEST') )
            return;
        
        global $wp, $locale;

        $request = $event->getRequest();
        
        $this->fixServerVars($request);
        
        // using cli,
        if( is_multisite() && php_sapi_name() == 'cli' ){
            
            $site = get_site_by_path( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] );
            
            if( $site && get_current_blog_id() != $site->blog_id ){

                switch_to_blog($site->blog_id);

                //reload locale
                unset($GLOBALS['locale']);

                load_default_textdomain();
                $locale = get_locale();

                $GLOBALS['wp_locale'] = new \WP_Locale();
                $GLOBALS['wp_locale_switcher'] = new \WP_Locale_Switcher();
                $GLOBALS['wp_locale_switcher']->init();
            }
        }
        
        $wp->init();

        if( $wp->parse_request() ){

            $wp->query_posts();
            $wp->register_globals();
        }

        do_action_ref_array( 'wp', array( &$wp ) );
        do_action( 'template_redirect' );
        do_action( 'kernel_loaded' );

        //Wordpress override $request, so restore it for Kernel shutdown
        global $request;
        $request = $event->getRequest();
    }
    
    
    /**
     * @param ResponseEvent $event
     * @return void
     * @throws \Exception
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        if( php_sapi_name() != 'cli' )
            return;

        $response = $event->getResponse();
        
        $default_uri = rtrim(env('DEFAULT_URI'), '/');

        $base_url = is_multisite() ? network_home_url() : get_home_url();
        $base_url = rtrim($base_url, '/');

        $content = $response->getContent();

        if( $base_url != $default_uri ){

            $content = str_replace($base_url, $default_uri, $content);
            $content = str_replace(substr(json_encode($base_url), 1 , -1), substr(json_encode($default_uri), 1 , -1), $content);
        }

        $base_url = 'http://localhost';

        if( $base_url != $default_uri ){

            $content = str_replace($base_url, $default_uri, $content);
            $content = str_replace(substr(json_encode($base_url), 1 , -1), substr(json_encode($default_uri), 1 , -1), $content);
        }

        $response->setContent($content);
    }
}
