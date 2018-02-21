<?php

/*
 * Route middleware to easily implement multi-langue
 * todo: check to find a better way...
 */
namespace Metabolism\WordpressLoader\Model;

use Symfony\Component\Routing\Matcher\UrlMatcher,
    Symfony\Component\Routing\RequestContext,
    Symfony\Component\Routing\RouteCollection,
    Symfony\Component\Routing\Loader\YamlFileLoader;

use Metabolism\WordpressLoader\Model\RouteModel as Route;

class RouterModel {


    protected $routes, $locale, $errors;

    public function __construct()
    {
    	$loader = new YamlFileLoader();

        $this->routes = new RouteCollection();
        $this->routes->addCollection( $loader->load('config/routing.yml') );
    }


    /**
     * Set locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }


    /**
     * Get current url path
     * @return string
     */
    private function get_current_url()
    {
        $current_url = ltrim(esc_url_raw(add_query_arg([])), '/');

	    $home_path = trim(parse_url(home_url(), PHP_URL_PATH), '/');
	    if ($home_path && strpos($current_url, $home_path) === 0)
		    $current_url = ltrim(substr($current_url, strlen($home_path)), '/');

	    $query_var_pos = strpos($current_url, '?');

	   if( $query_var_pos === false )
		   return '/'.$current_url;
	   else
		   return '/'.substr($current_url, 0, $query_var_pos);
    }


	/**
	 * Get ordered paramerters
	 * @return array
	 */
	private function getClosureArgs( $func ){

		$closure    = &$func;
		$reflection = new \ReflectionFunction($closure);
		$arguments  = $reflection->getParameters();

		$args = [];

		foreach ($arguments as $arg)
			$args[] = $arg->getName();

		return $args;
	}


    /**
     * Define route manager
     * @return bool|mixed
     */
    public function solve()
    {
	    $current_url = $this->get_current_url();

        $request_context = new RequestContext('/');
        $matcher = new UrlMatcher($this->routes, $request_context);

        $resource = $matcher->match($current_url);

        if( $resource and isset($resource['_controller']) )
        {
            $controller = $resource['_controller'];
            $args = $this->getClosureArgs($controller);

	        $resource['locale'] = $this->locale;

	        $params = [];

            foreach ($args as $arg)
	            $params[] = isset($resource[$arg])?$resource[$arg]:null;

            return call_user_func_array($controller, $params);
        }
        else
            return false;
    }


	/**
	 * Define error manager
	 * @param $code
	 * @return Route
	 * @internal param $pattern
	 * @internal param $controller
	 */
    public function error($code)
    {
	    if( isset($this->errors[$code] ) )
	    {
		    $controller = $this->errors[$code];
		    return call_user_func_array($controller, [$this->locale]);
	    }
	    else
	    	return false;
    }
}
