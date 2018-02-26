<?php

/*
 * Route middleware to easily implement multi-langue
 * todo: check to find a better way...
 */
namespace Metabolism\WordpressLoader\Model;

use Symfony\Component\Routing\Matcher\UrlMatcher,
    Symfony\Component\Routing\RequestContext,
    Symfony\Component\Routing\RouteCollection,
    Symfony\Component\Routing\Loader\YamlFileLoader,
	Symfony\Component\Config\FileLocator,
	Symfony\Component\HttpKernel\Controller\ControllerResolver,
	Symfony\Component\HttpKernel\Controller\ArgumentResolver,
	Symfony\Component\HttpFoundation\Request,
	Symfony\Component\HttpFoundation\Response;

use Metabolism\WordpressLoader\Model\RouteModel as Route;
use Metabolism\WordpressLoader\Controller\BaseController;

class RouterModel {


    protected $routes;

    public function __construct()
    {
	    $this->routes = new RouteCollection();

	    if( file_exists(BASE_URI.'/config/routing.yml') )
	    {
		    $loader = new YamlFileLoader(new FileLocator(BASE_URI));
		    $this->routes->addCollection( $loader->load('config/routing.yml') );
		    $this->routes->addPrefix(BASE_PATH);

		    $this->wordpressRewrite();
	    }
    }


    protected function wordpressRewrite(){

	    $routeIterator = $this->routes->getIterator();

	    global $_config;

	    foreach ($routeIterator as $name => $route) {

		    if( $wordpress = $route->getDefault('wordpress') )
		    {
			    $wordpress = explode(':', $wordpress);
			    $path = explode('/', $route->getPath());

			    $id  = array_shift($path);
			    $key = $wordpress[0].'.'.$id;

			    if( $wordpress[1] == 'single' )
			    	$key .= 'rewrite.slug';
			    if( $wordpress[1] == 'archive' )
				    $key .= 'has_archive';

			    $rewrite_slug = $_config->get($key, $id);

			    if( $rewrite_slug != $id )
			    {
				    $path[0] = $rewrite_slug;
				    $route->setPath( implode('/', $path));
			    }
		    }
	    }
    }


    /**
     * Define route manager
     * @return bool|mixed
     */
    public function resolve()
    {
	    $request = Request::createFromGlobals();

	    $context = new RequestContext();
	    $context->fromRequest($request);

	    $matcher = new UrlMatcher($this->routes, $context);

	    $controllerResolver = new ControllerResolver();
	    $argumentResolver = new ArgumentResolver();

	    try
	    {
	    	$match = $matcher->match($request->getPathInfo());

	    	$template = isset($match['template']) ? $match['template'] : false;

		    $request->attributes->add($match);

		    $controller = $controllerResolver->getController($request);
		    $arguments = $argumentResolver->getArguments($request, $controller);

		    if( $controller )
		    {
			    $response = call_user_func_array($controller, $arguments);
		    }
		    elseif( $template )
		    {
			    $controller = new BaseController();
			    $response = $controller->render($template);
		    }
	    }
	    catch (Routing\Exception\ResourceNotFoundException $e)
	    {
		    $response = new Response('Not Found', 404);
	    }
	    catch (Exception $e) {
		    $response = new Response('An error occurred', 500);
	    }

	    $response->send();
    }

}
