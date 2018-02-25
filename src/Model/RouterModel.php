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

class RouterModel {


    protected $routes;

    public function __construct()
    {
	    $this->routes = new RouteCollection();

	    if( !file_exists(BASE_URI.'config/routing.yml') ){

		    $loader = new YamlFileLoader(new FileLocator(BASE_URI));
		    $this->routes->addCollection( $loader->load('config/routing.yml') );
		    $this->routes->addPrefix(BASE_PATH);
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
		    $request->attributes->add($matcher->match($request->getPathInfo()));

		    $controller = $controllerResolver->getController($request);
		    $arguments = $argumentResolver->getArguments($request, $controller);

		    $response = call_user_func_array($controller, $arguments);
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
