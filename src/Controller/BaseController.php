<?php

namespace Metabolism\WordpressLoader\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class Metabolism\WordpressLoader Framework
 */
class BaseController {

	protected $context;

	/**
	 * Register route
	 * @param $pattern
	 * @param $controller
	 * @return Route
	 */
	public function render($path, $context=[], $status=200, $headers=[])
	{
		if( class_exists('\FrontBundle\Helper\SiteHelper') )
			$site = new \FrontBundle\Helper\SiteHelper();
		else
			$site = new \Metabolism\WordpressLoader\Helper\SiteHelper();

		return new Response( $site->fetch($path, $context), $status, $headers);
	}


	public function error($data, $status=500, $headers=[])
	{
		return new JsonResponse($data, $status, $headers);
	}


	public function json($data, $status=200, $headers=[])
	{
		return new JsonResponse($data, $status, $headers);
	}


	public function file($file, $status=200, $headers=[], $public=true)
	{
		return new BinaryFileResponse($file, $status, $headers, $public);
	}


	public function __construct()
	{
		if( class_exists('\FrontBundle\Helper\ContextHelper') )
			$this->context = new \FrontBundle\Helper\ContextHelper();
		else
			$this->context = new \Metabolism\WordpressLoader\Helper\ContextHelper();
	}
}
